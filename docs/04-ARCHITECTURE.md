# GitHub AI Reviewer --- System Architecture

## 1. Architecture Goal

The MVP uses a modular Laravel monolith.

This keeps deployment and development simple while maintaining clear
boundaries between GitHub integration, repository analysis, scoring, AI
integration, and persistence.

## 2. High-Level Architecture

``` text
User Browser
    |
    v
Laravel Web Application
    |
    +--> GitHub API
    |
    +--> Repository Analysis Layer
    |       +--> Documentation Analyzer
    |       +--> Testing Analyzer
    |       +--> Structure Analyzer
    |       +--> Security Analyzer
    |       +--> Git Practices Analyzer
    |       +--> Code Quality Analyzer
    |
    +--> Scoring Engine
    |
    +--> AI Review Service
    |       +--> AI Provider API
    |
    +--> SQLite (local development)
    |    Production database: TBD
    |
    v
Analysis Report
```

## 3. Application Layers

### Presentation Layer

Responsibilities:

-   Repository URL form
-   Validation feedback
-   Loading/error states
-   Report presentation
-   Analysis history

Technology: Blade + Tailwind CSS.

### Application Layer

Coordinates analysis workflows.

Suggested component: `AnalyzeRepositoryAction`.

This component should orchestrate:

1.  URL parsing
2.  GitHub retrieval
3.  Deterministic analysis
4.  Scoring
5.  AI review
6.  Persistence

Controllers should remain thin.

### GitHub Integration Layer

Suggested component: `GitHubRepositoryService`.

Responsibilities:

-   Parse owner/repository
-   Fetch metadata
-   Fetch repository tree
-   Fetch selected file contents
-   Fetch languages
-   Fetch commit information
-   Handle GitHub API errors
-   Handle rate-limit metadata

### Repository Analysis Layer

Use independent analyzers.

Possible contract: `RepositoryAnalyzerInterface`.

Possible analyzers:

-   DocumentationAnalyzer
-   TestingAnalyzer
-   ProjectStructureAnalyzer
-   SecurityAnalyzer
-   GitPracticesAnalyzer
-   CodeQualityAnalyzer

### Finding Model

Analyzers should produce a normalized structure:

-   category
-   severity
-   title
-   description
-   evidence
-   recommendation
-   source
-   rule_identifier

### Scoring Engine

Suggested component: `RepositoryScoringService`.

Responsibilities:

-   Receive deterministic analysis results.
-   Calculate category scores.
-   Apply category weights.
-   Produce overall score.
-   Record scoring explanations.

AI should not have unrestricted control over the final score.

### AI Layer

Suggested contract: `AIReviewerInterface`.

Responsibilities:

-   Construct safe prompts.
-   Select required repository context.
-   Request structured AI output.
-   Validate AI responses.
-   Convert AI observations into findings.

Repository content must be clearly delimited as untrusted data.

## 4. Suggested Laravel Structure

``` text
app/
├── Actions/
│   └── AnalyzeRepositoryAction.php
├── Contracts/
│   ├── RepositoryAnalyzerInterface.php
│   └── AIReviewerInterface.php
├── Services/
│   ├── GitHub/
│   │   └── GitHubRepositoryService.php
│   ├── Analysis/
│   │   ├── DocumentationAnalyzer.php
│   │   ├── TestingAnalyzer.php
│   │   ├── SecurityAnalyzer.php
│   │   ├── ProjectStructureAnalyzer.php
│   │   ├── GitPracticesAnalyzer.php
│   │   └── CodeQualityAnalyzer.php
│   ├── Scoring/
│   │   └── RepositoryScoringService.php
│   └── AI/
│       └── AIReviewService.php
├── Models/
│   ├── Repository.php
│   ├── Analysis.php
│   └── Finding.php
└── Http/
    └── Controllers/
```

## 5. Analysis Sequence

User submits URL → URL validation → GitHub metadata/tree retrieval →
relevant file selection → deterministic analyzers → normalized findings
→ scoring → AI review → persistence → report.

## 6. Failure Isolation

The system should support partial results.

If GitHub analysis succeeds but the AI provider fails:

-   Deterministic scores remain available.
-   Deterministic findings remain available.
-   Report displays AI review as temporarily unavailable.

## 7. Caching

Potential cached resources:

-   Repository metadata
-   Repository tree
-   Language information
-   Recent completed analysis

Caching should be introduced after observing actual API usage.

## 8. Queue Strategy

V1 may perform analysis synchronously if execution remains fast enough.

If analysis becomes slow, introduce Laravel Queue with jobs such as:

-   FetchRepositoryData
-   RunRepositoryAnalysis
-   GenerateAIReview

## 9. Security Boundaries

External inputs include:

-   User repository URL
-   GitHub API responses
-   Repository file contents
-   AI responses

All must be treated as untrusted.

Repository content must never be interpreted as application
instructions.

## 10. Future Evolution

The modular monolith can later evolve toward queues, Redis, dedicated
analysis workers, AI services, and a GitHub App.

A microservice architecture should only be introduced if operational
requirements justify the added complexity.
