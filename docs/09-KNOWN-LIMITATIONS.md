# Known Limitations

This page documents what GitHub AI Reviewer **does not** yet provide. It is not a bug list. Items are scoped by version/time reasoning, not planned dates unless stated in [ROADMAP](07-ROADMAP.md).

---

## Public Repositories Only

Private repositories require GitHub authentication OAuth or a GitHub App. We do not support authenticated requests. Users typing a private repository URL will get an API 404, same as any non-existent repository.

---

## Bounded Repository Snapshot

The contents directory collector makes up to 4 API calls per analysis:

1. Root directory listing
2. `.github` directory listing
3. One `.github` subdirectory (`.github/workflows` or `.github/ISSUE_TEMPLATE`)
4. `docs` directory listing

Remaining directories (e.g. a deeper project layout) or large trees beyond 200 entries per listing are marked `omittedBudget`. The report displays a note showing what was excluded.

Real reason: stricter GitHub API token limits without a token.

---

## snapshot Reproducibility

Two analyses of the same repo that are minutes apart may yield different paths if new commits arrived (like restored a missing README) different.

Commit SHA is recorded per analysis, so the exact snapshot point is known.

---

## No Branch Selection

All queries use the default branch reported by the API. A user cannot choose `develop` or an old tag.

---

## Single File Only

The bounded snapshot does not fetch file contents. Analyzer checks operate on file and directory signals, not on code content. A `README.md` file is detected but not read.

---

## No Code Quality Analysis

Code quality reporting checks only for `.editorconfig` presence. Implementations of static analysis (PHPStan, ESLint, etc) are planned for v1.1.

---

## No Data Caching

Each request hits the GitHub API; there is no client-side or server-side storage caching for metadata or trees. Repeated analysis of the same repository incurs the same API load.

---

## SQLite Only

Persistence is SQLite for local portability. Production deployment requires switching to the target database (MySQL/PostgreSQL) in `DB_CONNECTION`.

---

## No Authentication or Accounts

Everything is anonymous. There is no login, PDF export, or multi-user history. The tool is a simple single-user analysis assistant.

---

## AI Disclaimers

- The fake provider does not call any LLM or AI model.
- One provider implementation (OpenAI-compatible chat endpoint) exists.
- Responses of unknown provider behavior (non-compliant) are subject to `AIReviewResponseValidator` and fail validation.
- AI observations may contain unactionable advice on large repositories.
- AI review cannot revisit its own assessment from external hints — the prompt is immutable.

---

## Platform Constraints

- Built for desktop. Responsive phone layout works, but no mobile-specific design pass exists yet.
- No offline support. No PWA; completely server-side Blade rendering.

---

## Self-Verification

This list will be updated whenever the project adds new features or eliminates an existing limitation. Add an update date as a pull request description for each coverage-type removal moment.