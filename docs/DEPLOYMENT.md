# Deployment Guide

Production deployment reference for GitHub AI Reviewer. Covers environment
variables, database, storage, queue, cache, scheduler, and the Docker image.

This document describes configuration only. It does not pick a hosting
provider or provision secrets — see the "Provider selection" note at the end.

---

## Runtime requirements

- PHP 8.3+ with extensions: `pdo_sqlite` (or `pdo_pgsql`/`pdo_mysql`), `zip`, `intl`.
- Composer, Node.js 22+ (build time only).
- A TLS-terminating reverse proxy or CDN in front of the app (Cloudflare,
  Render's edge, nginx + Let's Encrypt, etc.). The app trusts forwarded
  headers via `bootstrap/app.php` so generated URLs use the public HTTPS
  origin; do not expose the PHP server directly to the internet.

---

## Environment variables

Copy `.env.example` to `.env` and fill in production values. The app ships a
boot guard in `AppServiceProvider::boot()` that forces `APP_DEBUG=false` in
any environment other than `local`, so a misconfigured debug flag cannot
leak stack traces in production.

### Required

| Variable | Production value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (also force-disabled by the boot guard) |
| `APP_KEY` | Fresh key from `php artisan key:generate` |
| `APP_URL` | Canonical public origin, e.g. `https://your-app.example.com` |
| `LOG_CHANNEL` | `stderr` so logs go to the platform collector |
| `LOG_LEVEL` | `warning` (or stricter) |

### Optional provider credentials

| Variable | Purpose |
| --- | --- |
| `GITHUB_TOKEN` | Raises GitHub REST API rate limit above the anonymous 60 req/hour |
| `AI_PROVIDER` | `fake` (no network) or a configured OpenAI-compatible provider id |
| `AI_BASE_URL` | OpenAI-compatible base URL |
| `AI_ENDPOINT` | Usually `chat/completions` |
| `AI_MODEL` | Provider model name |
| `AI_API_KEY` | Server-side key, redacted from failure logs automatically |
| `AI_TIMEOUT` | 5–120 seconds; out-of-range values are clamped |

Store all secrets on the host or in the platform's secret manager. Never
commit `.env` or paste credentials into chat.

---

## Database

The app defaults to SQLite (`DB_CONNECTION=sqlite`), which is file-backed and
zero-setup. It is ideal for local development and for a single-instance
production deploy that mounts a persistent volume.

### Recommendation

- **Single instance, simple deploy** → SQLite with a persistent volume. Zero
  external services; the only requirement is that `database/database.sqlite`
  lives on a mounted disk so it survives redeploys.
- **Multi-user / multi-instance / concurrent analysis** → PostgreSQL
  (recommended) or MySQL. Set `DB_CONNECTION=pgsql` (or `mysql`) plus
  `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and
  ensure the matching `pdo_*` extension is installed in the container.

### SQLite persistent path

Default: `database/database.sqlite` (a project-relative file). In production,
point it inside the mounted volume explicitly:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/app/database/database.sqlite
```

The Dockerfile currently installs `pdo_sqlite`. To switch to Postgres/MySQL,
change the `install-php-extensions` line in the Dockerfile to include
`pdo_pgsql` or `pdo_mysql` and rebuild.

### Migrations

Run on deploy:

```bash
php artisan migrate --force
```

The four migrations (users, cache, jobs, repositories/analyses/findings) plus
the session/jobs tables are included in the image. For PostgreSQL/MySQL, use
the app's managed migrations — no schema dump is committed.

---

## Storage

The app writes no user-uploaded files; `FILESYSTEM_DISK` stays `local`. The
only runtime-writable data is database (see above), logs, and framework
caches.

As a production single install across a persistent-volume deploy, storage
permissions must be writable for web server + CLI. The Dockerfile
`chown`s `storage` and `bootstrap/cache` to `www-data`.

For container-disposable environments (no persistent volume), remember any
runtime state is lost on redeploy — which is exactly why the database must be
external or on a persistent volume.

---

## Queue

Default `QUEUE_CONNECTION=database`. The app currently performs repository
analysis, GitHub fetch, deterministic scoring, and optional AI review
synchronously inside the web request (see `RepositorySubmissionController`).
It does not dispatch queued jobs yet, so no worker is strictly required.

If you later move analysis onto a queue, keep the connection on the same
persistent database as the app (default `database`) or on Redis, and add a
queued worker:

```bash
php artisan queue:work --tries=3 --timeout=120
```

Do **not** use `QUEUE_CONNECTION=sync` in production — it blocks the web
request until every job completes.

## Cache

Default `CACHE_STORE=database` (used by sessions/config/routes/views caching).
On a single instance this is fine. On multiple instances, point `CACHE_STORE`
(and `SESSION_DRIVER`) at a shared Redis so state is not split across hosts.

## Scheduler

Per `Dockerfile`, the CMD runs `config:cache`, `route:cache`, `view:cache`,
then starts the FrankenPHP server. There is **no scheduled task defined yet**
(`routes/console.php` only contains the default `inspire` command).

When you add scheduled tasks, there are two supported ways to run the Laravel
scheduler in a single container:

1. Run `php artisan schedule:work` as a sidecar process in the same container.
2. Run `php artisan schedule:run` from the host cron every minute:
   `* * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1`

The image already runs `view:cache` at boot; if you ever stop caching (during
a debugging window), remember to clear it with `php artisan optimize:clear`.

---

## Docker deploy steps

The project ships a multi-stage `Dockerfile` (Node 22 frontend build →
FrankenPHP PHP 8.3 runtime), a `.dockerignore`, and a `render.yaml` blueprint.

1. **Build the image.**

   ```bash
   docker build -t github-ai-reviewer .
   ```

2. **Prepare a persisted database file.** Create or retain
   `database/database.sqlite` on a persistent volume.

3. **Provide `.env`.** Either mount one, or set environment variables via your
   platform's secret/var config. Required at a minimum: `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_KEY`, `APP_URL`. Optional: `GITHUB_TOKEN`,
   `AI_PROVIDER`/`AI_BASE_URL`/`AI_ENDPOINT`/`AI_MODEL`/`AI_API_KEY`.

4. **Run migrations** against the persistent DB before/on first start:

   ```bash
   docker run --rm -v gh-data:/app/database github-ai-reviewer php artisan migrate --force
   ```

5. **Start the server.** The container CMD caches config/routes/views and runs
   FrankenPHP `php-server` listening on `$PORT` (default `8000`):

   ```bash
   docker run -d --name gh-reviewer \
     -v gh-data:/app/database \
     -e APP_ENV=production -e APP_DEBUG=false -e APP_KEY=... -e APP_URL=... \
     -p 8000:8000 github-ai-reviewer
   ```

6. **Proxy HTTPS in front** via Cloudflare, Render edge, or nginx + certbot.
   The app trusts `X-Forwarded-*` so links use the public origin.

7. **Verify** the readiness probe:
   - `GET /up` returns `200` when the app can bootstrap.
   - `GET /` returns the landing page.

### Persistent volume on render.yaml

If you deploy via `render.yaml`, attach a disk and set
`DB_DATABASE=/var/app/database/database.sqlite` so the SQLite file survives
redeploys.

---

## Provider selection

This guide deliberately leaves the hosting provider open. Choose based on
free-tier availability and requirements at deploy time (a current candidate
is Render, for which a `render.yaml` blueprint already exists). Whatever you
pick, the steps above (env, DB, key, migrations, HTTPS proxy) apply unchanged.

## Verification checklist

Before declaring a deploy healthy:

- `php artisan test` passes in CI (baseline 238 tests / 1049 assertions).
- `GET /up` → 200.
- `GET /` → 200 landing page.
- Submit a public repository URL and confirm the report renders with a score.
- Confirm `APP_DEBUG=false` — a deliberate 500 shows the branded error page,
  not a stack trace.
