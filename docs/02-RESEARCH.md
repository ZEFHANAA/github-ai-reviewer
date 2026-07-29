# GitHub AI Reviewer --- Research & Feasibility

## 1. Research Objective

This document identifies the major technical questions that must be
resolved before implementing GitHub AI Reviewer.

The goal is to reduce implementation risk and prevent unnecessary
architectural complexity.

## 2. GitHub Integration

The MVP will primarily use the GitHub REST API.

Potentially useful repository information includes:

-   Repository metadata
-   Default branch
-   Languages
-   Repository contents
-   Repository tree
-   Commits
-   Contributors
-   Branch information
-   README
-   License
-   Workflow files
-   Selected source files

## 3. Public Repository Scope

MVP analysis is limited to publicly accessible repositories.

Benefits:

-   No GitHub OAuth required for the initial release.
-   Simpler authorization model.
-   Lower security complexity.
-   Easier local development.

Authenticated GitHub API requests using a server-side token may still be
used to obtain more practical API limits.

The optional server-side configuration key is `GITHUB_TOKEN`. It must
never be exposed to the browser or committed to source control.

GitHub credentials must never be exposed to the browser.

## 4. API Rate Limits

GitHub applies API rate limits.

The application therefore needs to:

-   Avoid unnecessary API requests.
-   Cache reusable repository metadata where appropriate.
-   Detect rate-limit responses.
-   Display understandable error messages.
-   Avoid repeatedly analyzing the same repository within a short
    interval where possible.

Exact API limits should be confirmed against current GitHub
documentation during implementation.

## 5. Repository Size

Sending an entire repository to an AI model is inefficient and may
exceed context limits.

The application should therefore use selective analysis:

1.  Retrieve repository metadata.
2.  Retrieve the repository tree.
3.  Identify important files.
4.  Analyze deterministic characteristics locally.
5.  Select representative source/configuration files.
6.  Send only relevant context to the AI provider.

## 6. File Selection

Higher-priority files may include:

-   README.md
-   composer.json
-   package.json
-   requirements.txt
-   pyproject.toml
-   Dockerfile
-   docker-compose.yml
-   GitHub Actions workflows
-   Application entry points
-   Route definitions
-   Configuration examples
-   Controllers/services
-   Test files

Large generated files, dependencies, binaries, build output, and vendor
directories should be ignored.

Examples:

-   node_modules/
-   vendor/
-   dist/
-   build/
-   storage/
-   binary assets

## 7. Deterministic Analysis

Objective checks should be implemented without AI whenever practical.

Examples:

-   README exists.
-   LICENSE exists.
-   .gitignore exists.
-   Test directory exists.
-   CI configuration exists.
-   Dependency manifest exists.
-   Environment example exists.
-   Suspicious sensitive files appear in the repository.
-   Repository has recent commits.

Benefits include reproducible results, lower AI costs, faster analysis,
explainable scoring, and reduced hallucination risk.

## 8. AI Analysis

AI should primarily evaluate characteristics that are difficult to
represent using simple rules.

Potential tasks:

-   Explain code-quality concerns.
-   Evaluate README clarity.
-   Identify maintainability issues.
-   Explain architectural concerns.
-   Suggest improvements.
-   Summarize important findings.

AI output should use a structured response format whenever possible.

## 9. AI Reliability

AI findings are probabilistic.

Therefore:

-   AI observations must be labelled appropriately.
-   AI should not independently determine the entire repository score.
-   Security observations must be described as potential issues
    requiring verification.
-   Deterministic evidence should be preferred whenever available.

## 10. Security Considerations

Repository content must be treated as untrusted input.

The system must consider:

-   Prompt injection contained inside source code or README files.
-   Extremely large files.
-   Malformed API responses.
-   Embedded secrets.
-   HTML/script content.
-   Unexpected binary content.
-   URLs attempting to bypass GitHub URL validation.

Repository text sent to AI must be treated strictly as data, not
instructions.

## 11. AI Provider Abstraction

AI integration should not be tightly coupled to one provider.

Suggested abstraction: `AIReviewService`.

Possible implementations later:

-   OpenAI-compatible API
-   OpenRouter-compatible API
-   Local model
-   Other supported providers

This allows providers to be replaced without rewriting repository
analysis logic.

## 12. Deployment Feasibility

The MVP should remain compatible with inexpensive or free deployment
environments.

Heavy long-running analysis should therefore be avoided initially.

If analysis becomes computationally expensive, future versions may
introduce:

-   Queues
-   Redis
-   Dedicated workers
-   Separate analysis services

These are not required for the first implementation.

## 13. Technical Feasibility Conclusion

The MVP is technically feasible using:

-   Laravel
-   PHP
-   GitHub REST API
-   SQLite for local development
-   A production database selected later based on deployment requirements
-   Blade/Tailwind
-   An external AI API

Python and microservices are not required for V1.

The largest engineering risks are:

1.  GitHub API rate limits.
2.  Repository size.
3.  AI context limits and cost.
4.  Reliable file selection.
5.  False-positive security findings.
6.  Prompt injection from repository content.
