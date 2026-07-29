# GitHub AI Reviewer --- Scoring Specification

## 1. Objective

The scoring system must be explainable and reasonably reproducible.

A user should be able to understand why a repository received a
particular score.

## 2. Categories

The MVP uses six categories:

  Category              Weight
  ------------------- --------
  Documentation            20%
  Testing                  20%
  Security                 20%
  Project Structure        15%
  Code Quality             15%
  Git Practices            10%

Total: 100%.

## 3. Overall Formula

``` text
Overall Score =
Documentation × 0.20
+ Testing × 0.20
+ Security × 0.20
+ Project Structure × 0.15
+ Code Quality × 0.15
+ Git Practices × 0.10
```

Each category score ranges from 0 to 100.

## 4. Score Interpretation

-   90--100: Excellent
-   80--89: Good
-   70--79: Fair
-   60--69: Needs Improvement
-   0--59: Poor

These labels describe repository health indicators, not developer
ability.

## 5. Documentation

Possible indicators:

-   README exists.
-   README contains project description.
-   Installation/setup instructions exist.
-   Usage information exists.
-   Environment configuration is explained.
-   LICENSE exists.
-   Contribution guidance may provide additional credit.

Rules should adapt where a particular indicator is not relevant.

## 6. Testing

Possible indicators:

-   Test directory/files detected.
-   Recognized test framework configured.
-   Testing instructions exist.
-   CI executes tests.
-   Multiple meaningful test files exist.

File presence does not prove test quality.

The report must not claim code coverage unless coverage information is
actually available.

## 7. Security

Possible indicators:

-   No obvious committed `.env` file.
-   No obvious credential files.
-   No obvious secret patterns identified.
-   Environment template is used appropriately.
-   Dependency/security configuration may be inspected where practical.

A detected pattern is a potential issue, not proof of credential
validity.

## 8. Project Structure

Possible indicators:

-   Recognizable source organization.
-   Dependency manifest exists.
-   Generated/dependency directories are excluded.
-   Configuration organization is reasonable.
-   Separation between source and tests exists where applicable.
-   Framework conventions may be considered when identified reliably.

## 9. Code Quality

Potential deterministic indicators:

-   Extremely large source files.
-   Duplicated configuration patterns where detectable.
-   Debug statements or obvious development artifacts.
-   Basic complexity indicators where reliable.

AI may provide qualitative code-quality findings but should not
arbitrarily replace the deterministic score.

## 10. Git Practices

Possible indicators:

-   Repository contains meaningful commit history.
-   Recent activity exists.
-   CI workflow exists.
-   Repository hygiene files exist.
-   Commit-message indicators may be analyzed when enough history
    exists.

Repository age must be considered before penalizing low commit counts.

## 11. Finding Severity

### INFO

Informational observation with no immediate concern.

### LOW

Minor quality or maintainability improvement.

### MEDIUM

Meaningful issue that should be addressed.

### HIGH

Potentially serious security, reliability, or maintainability issue.

MVP intentionally excludes "Critical" until sufficiently reliable rules
exist.

## 12. Penalties

Scores should be calculated from documented rules rather than arbitrary
AI judgement.

Example conceptual rule:

``` text
Rule: SEC-ENV-001
Condition: A committed file named ".env" is detected.
Category: Security
Severity: High
Effect: Apply defined security penalty.
```

Recommendation:

Remove the file from version control, verify whether secrets were
exposed, rotate affected credentials if necessary, and use an
environment example file.

Exact numerical penalties should be calibrated using real repositories
during development.

## 13. Rule Identifiers

Every deterministic scoring rule should have an identifier.

Examples:

-   DOC-README-001
-   DOC-INSTALL-002
-   TEST-DIR-001
-   TEST-CI-002
-   SEC-ENV-001
-   STRUCT-DEPS-001
-   GIT-CI-001

Benefits:

-   Debugging
-   Testing
-   Score explanations
-   Future rule versioning

## 14. AI and Scoring

AI should primarily:

-   Explain findings.
-   Identify qualitative concerns.
-   Suggest improvements.
-   Summarize repository health.

AI should not freely invent the final score.

## 15. Missing Information

Absence of evidence does not always mean poor quality.

For example, a repository without GitHub Actions may use another CI
provider.

Reports should distinguish:

> CI not detected

from:

> This repository has no CI.

## 16. Calibration

Before releasing scoring publicly:

1.  Select repositories of different quality levels.
2.  Run the analyzer.
3.  Inspect false positives.
4.  Compare results manually.
5.  Adjust rules and weights.
6.  Document changes.

The score should be treated as a repository health heuristic, not an
objective universal quality measurement.
