# Features

This document describes what GitHub AI Reviewer currently does. It is based on
running code, not aspirational design; if something is listed below, tests exercise it.

---

## Core Workflow

1. User opens the landing page.
2. User submits a canonical public GitHub HTTPS URL (e.g. `https://github.com/laravel/laravel`).
3. Application fetches metadata via `/repos/{owner}/{repo}`.
4. Application collects repository directory tree via the GitHub Contents API — root listing plus up to 3 additional subdirectory requests (`.github`, `.github/workflows`, `docs` or `.github/ISSUE_TEMPLATE`) bounded at 4 total Content API calls and 200 directory entries per response.
5. Deterministic analysis produces typed findings.
6. Category scores are calculated (0-100 each) and combined into a weighted overall score.
7. Optional AI review enriches the deterministic report.
8. The full analysis is persisted to SQLite.
9. A single-page Blade report presents scores, findings (with filters), AI comments, and metadata.

---

## URL Validation

- Only canonical `https://github.com/{owner}/{name}` URLs are accepted.
- `www.github.com`, `api.github.com`, valid-looking `some-github.tld` subdomains with SSH-style segments, and URLs containing extra path components or query parameters are rejected in the after-validation hook.
- Maximum input length of 2048 characters.

---

## Deterministic Analysis

20 deterministic checks across six categories. Each finding carries:

- Rule identifier, category, status (Pass / Improvement / Unknown), severity (P1 / P2 / P3)
- Description, evidence (when available), recommendation

### Documentation
DOC-README-001, DOC-LICENSE-001, DOC-CONTRIBUTING-001, DOC-CONDUCT-001, DOC-CHANGELOG-001

### Community & Governance
COMM-ISSUE-001, COMM-BUG-001, COMM-FEATURE-001, COMM-PR-001

### Testing
TEST-DIRECTORY-001, TEST-CONFIG-001

### Security Hygiene
SEC-ENV-001, SEC-POLICY-001, SEC-DEPENDABOT-001, SEC-CODEQL-001

### Project Structure
STRUCT-MANIFEST-001, STRUCT-SOURCE-001

### Git Practices
GIT-CI-001, GIT-IGNORE-001

### Code Quality
CODE-CONFIG-001

Checks explicitly avoid assuming missing signals are proof that the project lacks something. When data was unavailable because an API call failed or a budget limit was hit, checks return `Unknown`.

---

## Scoring

| Category | Weight |
| --- | --- |
| Documentation | 25% |
| Testing | 15% |
| Security Hygiene | 25% |
| Project Structure | 15% |
| Git Practices | 10% |
| Code Quality | 10% |

- Pass = 100%, Improvement = 0%, Unknown = weighted lower (mitigation: "we can not inspect this area so the penalty is bounded").
- Grouped checks prevent double-penalty for correlated misses (e.g. missing issue/PR templates are a single group penalty, not four separate hits).
- The report and its scores are fully deterministic for the same snapshot.

---

## AI Review

Fake provider (default): deterministic demo output, no network calls. Returns structured documentation concerns, maintainability observations, and recommendations based on deterministic findings.

OpenAI-compatible provider: sends a controlled prompt to a `/chat/completions` endpoint and maps/validates the structured JSON response into typed observations.

### AI Safety

- Prompt builder: max ~16 KB (firm) — truncates finding descriptions.
- AI timeout: 30 s configurable, clamped 5–120 s.
- AI response goes through schema validation; invalid responses fail to `AIReviewOutcome::unavailable()`.
- AI unable to change scores or deterministic findings.
- AI failure gracefully falls back to the deterministic report with an "AI review unavailable" message — no breaking.

---

## Persistence

### Entities
`Repository`, `Analysis`, `Finding`.

### Behavior
- New analysis on re-submit — no update-in-place.
- Repositories deduplicated by full name (owner/name).
- Snapshots track branch, commit SHA, and unanalyzed/unavailable/omitted keys when the budget was exhausted.
- Partial result capability — analysis persists even AI review fails.

---

## Filters

Report UI provides filtering data attributes (`data-category`, `data-status`, `data-severity`) on every finding row, supporting client-side toggling.

---

## Error States

| Condition | Status | Behavior |
| --- | --- | --- |
| `example.com` URL | 302 | Redirect back with inline error |
| Repository not found (404) | 404 | Friendly "not found" page |
| GitHub 500 / time-out | 503 | Friendly "GitHub unavailable" page |
| Rate-limit (429 / 403+X-RateLimit-Remaining=0) | 429 | Friendly "rate-limited" page |
| Rate-limiting on our side | 429 | "Too many analyses" page |
| AI provider failure | normal | Deterministic report + "AI unavailable" note |

---

## Rate Limiting

Submission endpoint: 10 analyses per minute per client IP.

---

## Technology

- Laravel 13
- PHP 8.3+ / Composer
- SQLite
- Tailwind CSS via Vite
- Blade templating
- No JS framework dependency

---

## Security

- Server-side credentials only (GitHub token, AI key, AI base URL as environment variables).
- HTTP client secret redaction before failure logging.
- All Blade output auto-escaped.
- GitHub API and AI provider responses are validated and fail-closed.
- Rate limits on resource-intensive actions.
- No authentication system (server-side config only).
- This tool is not a professional security audit.