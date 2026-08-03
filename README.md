# GitHub AI Reviewer

AI-assisted GitHub repository health analyzer built with Laravel.

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Tests](https://img.shields.io/badge/tests-217%20passing-brightgreen)](#verification)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

## What It Does

Submit a public GitHub repository URL. The application retrieves bounded repository metadata and path information, runs deterministic checks, calculates explainable scores, and adds optional qualitative AI observations.

Deterministic analysis owns scores and findings. AI explains selected findings and suggests improvements; it cannot change deterministic scores or findings. AI failure falls back to the deterministic report.

## Current Features

- Canonical HTTPS GitHub repository URL validation
- GitHub metadata and bounded Contents API collection
- Request and directory-entry limits
- Documentation, testing, security hygiene, project structure, Git-practice, and code-quality checks
- Typed findings with category, status, scope, severity, evidence, recommendation, and source
- Category scores plus weighted overall score
- Severity/status/category finding filters
- Optional AI review through fake or OpenAI-compatible providers
- AI response validation, timeout policy, prompt-size limits, and failure isolation
- Analysis persistence in SQLite
- Friendly invalid URL, GitHub failure, rate-limit, and AI-unavailable states
- Secret redaction before provider failures reach logs

## Score Weights

| Category | Weight |
| --- | ---: |
| Documentation | 25% |
| Testing | 15% |
| Security Hygiene | 25% |
| Project Structure | 15% |
| Git Practices | 10% |
| Code Quality | 10% |

Scores use deterministic rules and are normalized to a 0-100 range. They describe repository-health indicators, not developer ability or a professional security audit.

## Architecture

```text
Browser
  |
  v
Laravel route/controller
  |
  +--> GitHubRepositoryService --> RepositorySnapshot
  |                                   |
  |                                   v
  +--> Deterministic analyzer --> typed findings --> scoring
  |                                                   |
  +--> Safe AI review (optional) --------------------+
  |
  +--> persistence --> Blade report
```

The full diagram is in [`docs/architecture.svg`](docs/architecture.svg). Engineering details live in [`docs/04-ARCHITECTURE.md`](docs/04-ARCHITECTURE.md).

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- SQLite with the PHP SQLite extension

## Installation

```bash
git clone https://github.com/ZEFHANAA/github-ai-reviewer.git
cd github-ai-reviewer
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

For frontend development with hot reload:

```bash
npm run dev
```

Do not commit `.env`, API keys, tokens, or generated local database files.

## Configuration

`.env.example` contains safe defaults.

| Variable | Purpose | Default |
| --- | --- | --- |
| `GITHUB_TOKEN` | Optional server-side GitHub API token | empty |
| `AI_PROVIDER` | `fake` or configured OpenAI-compatible provider | `fake` |
| `AI_BASE_URL` | OpenAI-compatible base URL | empty |
| `AI_ENDPOINT` | Provider endpoint | `chat/completions` |
| `AI_MODEL` | Provider model name | empty |
| `AI_API_KEY` | Server-side provider key | empty |
| `AI_TIMEOUT` | Request timeout, clamped to 5-120 seconds | `30` |

Fake provider mode avoids network AI calls and produces deterministic demo output.

## Verification

```bash
php artisan test
vendor/bin/pint --test
npm run build
git diff --check
```

Current baseline: **217 tests passed, 957 assertions**.

## Security Boundary

- Only public GitHub repositories are supported.
- Repository content is untrusted input and is isolated before AI review.
- AI output is validated and rendered with escaped Blade output.
- Provider and GitHub credentials stay server-side and are redacted from failure logs.
- Rate limiting protects repository-analysis submissions.
- This tool is not a professional security audit and does not prove vulnerabilities or safety.

## Portfolio Materials

- [Feature documentation](docs/08-FEATURES.md)
- [Known limitations](docs/09-KNOWN-LIMITATIONS.md)
- [Demo report](docs/10-DEMO-REPORT.md)
- [Deployment guide](docs/DEPLOYMENT.md)
- [Architecture diagram](docs/architecture.svg)
- [Development roadmap](docs/07-ROADMAP.md)
- [Engineering documentation](docs/)

Reproducible local screenshots using the fake AI provider are documented in [the demo walkthrough](docs/10-DEMO-REPORT.md).

## Project Status

Phases 1-10 are complete. Phase 11 configuration and deployment
documentation are in place; the application has a deployment guide
([`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)) covering environment variables,
database, storage, queue, cache, scheduler, and Docker steps. There is no
public demo deployment claim.

## License

MIT. See [`LICENSE`](LICENSE).
