# GitHub AI Reviewer --- Database Design

## 1. Design Goals

The database should:

-   Store repositories without unnecessary duplication.
-   Support multiple analyses of the same repository.
-   Preserve historical scores.
-   Store individual findings.
-   Distinguish deterministic findings from AI findings.
-   Support future trend analysis.

## 2. Entity Relationship

``` text
Repository
    1
    |
    N
Analysis
    1
    |
    N
Finding
```

## 3. repositories

Stores GitHub repository identity and cached metadata.

Fields:

-   id --- BIGINT primary key
-   github_id --- BIGINT nullable/unique where available
-   owner --- VARCHAR
-   name --- VARCHAR
-   full_name --- VARCHAR
-   url --- VARCHAR
-   description --- TEXT nullable
-   primary_language --- VARCHAR nullable
-   default_branch --- VARCHAR nullable
-   stars_count --- INTEGER default 0
-   forks_count --- INTEGER default 0
-   github_created_at --- TIMESTAMP nullable
-   github_updated_at --- TIMESTAMP nullable
-   created_at
-   updated_at

Recommended unique constraint: `owner + name`.

## 4. analyses

Represents one repository analysis execution.

Fields:

-   id --- BIGINT primary key
-   repository_id --- foreign key
-   status --- VARCHAR
-   overall_score --- DECIMAL nullable
-   documentation_score --- DECIMAL nullable
-   code_quality_score --- DECIMAL nullable
-   testing_score --- DECIMAL nullable
-   security_score --- DECIMAL nullable
-   project_structure_score --- DECIMAL nullable
-   git_practices_score --- DECIMAL nullable
-   ai_summary --- LONGTEXT nullable
-   ai_provider --- VARCHAR nullable
-   ai_model --- VARCHAR nullable
-   github_commit_sha --- VARCHAR nullable
-   started_at --- TIMESTAMP nullable
-   completed_at --- TIMESTAMP nullable
-   created_at
-   updated_at

Possible status values:

-   pending
-   running
-   completed
-   partial
-   failed

## 5. findings

Stores individual observations.

Fields:

-   id --- BIGINT primary key
-   analysis_id --- foreign key
-   category --- VARCHAR
-   severity --- VARCHAR
-   source --- VARCHAR
-   rule_identifier --- VARCHAR nullable
-   title --- VARCHAR
-   description --- TEXT
-   evidence --- TEXT nullable
-   recommendation --- TEXT nullable
-   file_path --- VARCHAR nullable
-   line_reference --- VARCHAR nullable
-   created_at
-   updated_at

Supported source values:

-   rule
-   ai

Supported severity values:

-   info
-   low
-   medium
-   high

## 6. Why Scores Belong to Analysis

Repository quality changes over time.

If scores were stored directly on repositories, historical information
would be lost.

Storing scores on analyses enables future trend charts.

## 7. Commit SHA

Each analysis should attempt to record the default branch commit SHA
used during analysis.

This provides traceability if the repository changes later.

## 8. Raw Repository Content

Avoid storing complete repository source code unless there is a clear
requirement.

Prefer storing:

-   Repository identifiers
-   Analysis metadata
-   Findings
-   Evidence snippets where necessary

## 9. AI Data

Store useful AI output such as:

-   Summary
-   Findings
-   Provider
-   Model

Avoid persisting complete prompts containing large source-code excerpts
unless debugging explicitly requires it.

## 10. Future Tables

Potential future additions:

-   users
-   github_connections
-   analysis_jobs
-   shared_reports
-   repository_watchlists
-   pull_request_reviews
-   analysis_metrics

These are outside the MVP.

## 11. Indexing

Useful indexes may include:

-   repositories.owner + repositories.name
-   analyses.repository_id
-   analyses.created_at
-   analyses.status
-   findings.analysis_id
-   findings.category
-   findings.severity

Do not introduce unnecessary indexes before observing query patterns.
