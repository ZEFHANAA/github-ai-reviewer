# GitHub AI Reviewer --- Product Requirements Document

## 1. Product Name

Working name: **GitHub AI Reviewer**.

The product may receive a unique brand name before public release.

## 2. Product Summary

GitHub AI Reviewer is a web application that analyzes public GitHub
repositories using deterministic repository checks and AI-assisted
review.

It generates an explainable repository health report containing category
scores, findings, severity levels, and actionable recommendations.

## 3. Goals

The product should:

-   Make repository quality easier to understand.
-   Identify common repository weaknesses.
-   Provide actionable improvement recommendations.
-   Combine deterministic analysis with AI.
-   Produce transparent and explainable scoring.
-   Help developers improve portfolio and production repositories.

## 4. Non-Goals for MVP

The MVP will not:

-   Access private repositories.
-   Automatically modify source code.
-   Create Pull Requests.
-   Replace professional security auditing.
-   Guarantee vulnerability detection.
-   Perform full static analysis for every programming language.
-   Review Pull Requests in real time.

## 5. User Story

As a GitHub repository owner or reviewer, I want to submit a public
GitHub repository so that I can understand its overall health, identify
weaknesses, and receive recommendations for improvement.

## 6. Primary User Flow

1.  User opens the application.
2.  User enters a GitHub repository URL.
3.  System validates the URL.
4.  System identifies repository owner and name.
5.  System requests repository information from GitHub.
6.  System retrieves relevant repository information and files.
7.  Deterministic analyzers execute.
8.  Scoring engine calculates category scores.
9.  Selected repository context is sent to the AI reviewer.
10. AI response is validated.
11. Final report is generated.
12. User views scores, findings, and recommendations.

## 7. MVP Features

### Repository Input

The application must:

-   Accept GitHub repository URLs.
-   Validate supported URL format.
-   Reject unsupported hosts.
-   Detect invalid/non-existent repositories.
-   Detect inaccessible/private repositories.

### Repository Metadata

Display:

-   Repository name
-   Owner
-   Description
-   Primary language
-   Stars
-   Forks
-   Default branch
-   License where available
-   Last update information

### Documentation Analysis

Analyze indicators such as:

-   README availability
-   README quality indicators
-   Installation/setup documentation
-   Usage documentation
-   LICENSE
-   Environment configuration documentation

### Testing Analysis

Analyze indicators such as:

-   Presence of test directories/files
-   Test framework configuration
-   Testing instructions
-   CI test workflows where detectable

The MVP must not claim exact test coverage unless actual coverage data
is available.

### Project Structure Analysis

Analyze:

-   Recognizable project structure
-   Dependency manifests
-   Configuration organization
-   Separation of source and generated files
-   Presence of common development files

### Git Practices Analysis

Analyze available indicators such as:

-   Repository activity
-   Commit history availability
-   CI/CD configuration
-   Repository hygiene files

Commit-message quality may be considered where sufficient history
exists.

### Security Analysis

Perform basic repository hygiene checks such as:

-   Suspicious sensitive filenames
-   Potentially committed environment files
-   Obvious credential-pattern indicators where safely detectable
-   Security-related configuration observations

The application must clearly state that this is not a professional
security audit.

### AI Review

AI should provide:

-   Repository summary
-   Code-quality observations
-   Documentation observations
-   Maintainability observations
-   Potential concerns
-   Prioritized recommendations

### Scoring

Display:

-   Overall Score
-   Documentation Score
-   Code Quality Score
-   Testing Score
-   Security Score
-   Project Structure Score
-   Git Practices Score

### Findings

Each finding should contain:

-   Category
-   Severity
-   Title
-   Description
-   Evidence where appropriate
-   Recommendation
-   Source: deterministic or AI

Supported severities:

-   Info
-   Low
-   Medium
-   High

## 8. Analysis History

The application should store completed analysis reports.

MVP may allow recent reports to be viewed without requiring a user
account.

Authentication can be introduced later.

## 9. Error States

The application must gracefully handle:

-   Invalid URL
-   Non-GitHub URL
-   Repository not found
-   Private repository
-   GitHub API unavailable
-   GitHub API rate limit
-   AI provider unavailable
-   Invalid AI response
-   Repository too large
-   Analysis timeout

A failure of AI analysis should not necessarily prevent deterministic
results from being shown.

## 10. Performance Requirements

The application should:

-   Avoid downloading unnecessary repository content.
-   Limit AI input.
-   Avoid blocking operations where possible.
-   Reuse recent analysis where appropriate.

Initial performance targets should be measured during implementation
before strict SLAs are defined.

## 11. Security Requirements

-   API keys remain server-side.
-   Secrets are stored through environment variables.
-   GitHub URLs are strictly validated.
-   Repository content is treated as untrusted input.
-   AI prompts isolate repository content from system instructions.
-   AI output is escaped before rendering.
-   Requests are rate-limited.
-   Sensitive repository content should not be unnecessarily persisted.

## 12. Privacy

The MVP only analyzes public GitHub repositories.

The application should clearly disclose when repository content is sent
to an external AI provider.

## 13. Technology

-   Backend: Laravel / PHP
-   Frontend: Blade + Tailwind CSS
-   Database: SQLite for local development; production database TBD based
    on deployment requirements
-   Repository Provider: GitHub REST API
-   AI: Provider abstraction using an API-based LLM

## 14. Acceptance Criteria

The MVP is complete when:

-   A user can submit a public GitHub repository URL.
-   Repository metadata is successfully retrieved.
-   Repository structure can be inspected.
-   Deterministic checks execute.
-   Category scores are generated.
-   AI review can execute.
-   Findings identify their source.
-   A complete report is displayed.
-   Common API failures are handled.
-   Analysis results can be stored.
-   Secrets are not exposed client-side.
-   Basic automated tests cover critical application logic.

## 15. Future Scope

Potential future capabilities:

-   GitHub OAuth
-   Private repositories
-   User accounts
-   Compare repositories
-   Analysis trends
-   Pull Request reviews
-   Suggested patches
-   Automatic Pull Requests
-   GitHub App
-   Team dashboard
-   Organization monitoring
-   IDE extension
-   Public report sharing
