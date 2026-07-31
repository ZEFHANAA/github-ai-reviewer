<?php

namespace App\Services\Analysis;

use App\Analysis\AnalysisFinding;
use App\Analysis\RuleRegistry;
use App\Enums\FindingScope;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use App\ValueObjects\RepositorySnapshot;

class DeterministicRepositoryAnalysisService
{
    /** @return list<AnalysisFinding> */
    public function analyze(RepositorySnapshot $s): array
    {
        $readme = $s->starts('readme');
        $license = $s->starts('license');

        // Rule B: Contribution guidance
        $hasContributing = $this->matchAnyPath($s, ['contributing', 'contributing.md', '.github/contributing.md', 'docs/contributing.md']);
        $contributing = $hasContributing ? true : ($this->isUnavailable($s, '.github') || $this->isUnavailable($s, 'docs') ? null : false);

        // Rule D: Community & governance
        $hasSecurity = $this->matchAnyPath($s, ['security', 'security.md', '.github/security.md', 'docs/security.md']);
        $security = $hasSecurity ? true : ($this->isUnavailable($s, '.github') || $this->isUnavailable($s, 'docs') ? null : false);

        $hasConduct = $this->matchAnyPath($s, ['code_of_conduct', 'code_of_conduct.md', '.github/code_of_conduct.md', 'docs/code_of_conduct.md']);
        $conduct = $hasConduct ? true : ($this->isUnavailable($s, '.github') || $this->isUnavailable($s, 'docs') ? null : false);

        $hasChangelog = $this->matchAnyPath($s, ['changelog', 'changelog.md', '.github/changelog.md', 'docs/changelog.md', 'releases']);
        $changelog = $hasChangelog ? true : ($this->isUnavailable($s, '.github') || $this->isUnavailable($s, 'docs') ? null : false);

        // Rule E: GitHub Community Health templates
        $githubUnavailable = $this->isUnavailable($s, '.github');

        $hasIssueTemplate = $this->matchAnyPath($s, [
            '.github/issue_template',
            '.github/issue_template.md',
            '.github/ISSUE_TEMPLATE',
            '.github/ISSUE_TEMPLATE.md',
        ]) || $s->starts('.github/issue_template/') || $s->starts('.github/ISSUE_TEMPLATE/');
        $issueTemplate = $hasIssueTemplate ? true : ($githubUnavailable ? null : false);

        $hasBugTemplate = $s->starts('.github/issue_template/') || $s->starts('.github/ISSUE_TEMPLATE/')
            ? collect($s->paths)->contains(fn ($p) => $this->matchIssueTemplateBasename($p, ['bug']))
            : $this->matchAnyPath($s, ['.github/issue_template/bug', '.github/issue_template/bug.md']);
        $bugTemplate = $hasBugTemplate ? true : ($githubUnavailable ? null : false);

        $hasFeatureTemplate = $s->starts('.github/issue_template/') || $s->starts('.github/ISSUE_TEMPLATE/')
            ? collect($s->paths)->contains(fn ($p) => $this->matchIssueTemplateBasename($p, ['feature']))
            : $this->matchAnyPath($s, ['.github/issue_template/feature', '.github/issue_template/feature.md']);
        $featureTemplate = $hasFeatureTemplate ? true : ($githubUnavailable ? null : false);

        $hasPrTemplate = $this->matchAnyPath($s, [
            '.github/pull_request_template',
            '.github/pull_request_template.md',
            '.github/PULL_REQUEST_TEMPLATE',
            '.github/PULL_REQUEST_TEMPLATE.md',
        ]) || $s->starts('.github/pull_request_template/') || $s->starts('.github/PULL_REQUEST_TEMPLATE/') || $this->matchAnyPath($s, ['docs/pull_request_template', 'docs/pull_request_template.md']);
        $prTemplate = $hasPrTemplate ? true : ($githubUnavailable ? null : false);

        // Rule A: Test detection
        $hasTests = $this->matchAnyPath($s, ['tests', 'test', 'spec', 'specs', '__tests__']);
        // Rule A Requirement: "Do not assume that missing test signals prove that the project has no tests." -> return unknown (null)
        $tests = $hasTests ? true : null;

        $testConfig = $s->has('phpunit.xml') || $s->has('pest.php') || $s->has('jest.config.js') || $s->has('vitest.config.js') || $s->has('pytest.ini');

        $env = collect($s->paths)->first(fn ($p) => preg_match('#(^|/)\\.env(?:\\..+)?$#i', $p) && ! str_ends_with(strtolower($p), '.example'));
        $dependabotDetected = $s->has('.github/dependabot.yml') || $s->has('.github/dependabot.yaml');
        $dependabot = $dependabotDetected ? true : ($githubUnavailable ? null : false);
        $workflowUnavailable = $this->isUnavailable($s, '.github/workflows');
        $hasWorkflow = collect($s->paths)->contains(fn ($p) => preg_match('#^\\.github/workflows/[^/]+\\.(yml|yaml)$#i', $p) === 1);
        $ci = $hasWorkflow ? true : ($workflowUnavailable ? null : false);
        $hasCodeql = collect($s->paths)->contains(fn ($p) => preg_match('#^\\.github/workflows/(codeql|codeql-analysis)\\.(yml|yaml)$#i', $p) === 1);
        $codeql = $hasCodeql ? true : ($workflowUnavailable ? null : false);
        $manifestNames = array_map(strtolower(...), [
            'composer.json',
            'package.json',
            'pyproject.toml',
            'requirements.txt',
            'setup.py',
            'setup.cfg',
            'Pipfile',
            'Cargo.toml',
            'go.mod',
            'pom.xml',
            'build.gradle',
            'build.gradle.kts',
            'Gemfile',
            'pubspec.yaml',
            'mix.exs',
            'Package.swift',
        ]);
        $manifestFiles = collect($s->paths)
            ->filter(fn ($p) => ! str_contains($p, '/'))
            ->filter(function (string $path) use ($manifestNames): bool {
                $basename = strtolower(basename($path));
                if (in_array($basename, $manifestNames, true)) {
                    return true;
                }
                foreach (['csproj', 'fsproj', 'sln'] as $suffix) {
                    if (str_ends_with($basename, '.'.$suffix)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(fn ($p) => basename($p))
            ->values()
            ->all();
        $manifest = $manifestFiles !== [];

        // Rule C: Source organization
        $hasSource = $this->matchAnyPath($s, ['app', 'src', 'lib']);
        $source = $hasSource ? true : null;

        $ignore = $s->has('.gitignore');
        $editor = $s->has('.editorconfig');

        return [
            $this->finding('DOC-README-001', $readme, $this->resolveScope($s), 'README', 'README documentation was detected.', 'README not detected. This is an improvement opportunity, not proof that documentation is absent elsewhere.', 'Add a README explaining setup and usage.'),
            $this->finding('DOC-LICENSE-001', $license, $this->resolveScope($s), 'License', 'A license file was detected.', 'License not detected.', 'Add an appropriate license file.'),
            $this->finding('DOC-CONTRIBUTING-001', $contributing, $this->resolveScope($s, '.github', 'docs'), 'Contribution guidance', 'Contribution guidance was detected.', 'Contribution guidance not detected.', 'Consider adding CONTRIBUTING.md.'),
            $this->finding('DOC-CONDUCT-001', $conduct, $this->resolveScope($s, '.github', 'docs'), 'Code of conduct', 'Code of conduct was detected.', 'Code of conduct not detected.', 'Consider adding CODE_OF_CONDUCT.md.'),
            $this->finding('DOC-CHANGELOG-001', $changelog, $this->resolveScope($s, '.github', 'docs'), 'Changelog', 'Changelog documentation was detected.', 'Changelog not detected.', 'Consider maintaining a CHANGELOG.md.'),
            $this->finding('COMM-ISSUE-001', $issueTemplate, $this->resolveScope($s, '.github'), 'Issue templates', 'Issue templates directory or issue forms were detected.', 'Issue templates were not detected.', 'Consider adding issue templates in .github/ISSUE_TEMPLATE.'),
            $this->finding('COMM-BUG-001', $bugTemplate, $this->resolveScope($s, '.github'), 'Bug report template', 'Bug report issue template was detected.', 'Bug report template was not detected.', 'Consider adding a bug report template or form.'),
            $this->finding('COMM-FEATURE-001', $featureTemplate, $this->resolveScope($s, '.github'), 'Feature request template', 'Feature request issue template was detected.', 'Feature request template was not detected.', 'Consider adding a feature request template or form.'),
            $this->finding('COMM-PR-001', $prTemplate, $this->resolveScope($s, '.github'), 'Pull request template', 'Pull request template was detected.', 'Pull request template was not detected.', 'Consider adding a pull request template.'),
            $this->finding('TEST-DIRECTORY-001', $tests, $this->resolveScope($s), 'Test files', 'Recognizable test paths were detected.', 'Recognizable test paths were not detected. This does not measure coverage or test quality.', 'Add automated tests in a conventional directory.'),
            $this->finding('TEST-CONFIG-001', $testConfig, $this->resolveScope($s), 'Test configuration', 'A recognizable test configuration file was detected.', 'A recognizable test configuration file was not detected.', 'Add or document the test runner configuration.'),
            new AnalysisFinding('SEC-ENV-001', RuleCategory::SecurityHygiene, $env ? FindingStatus::Improvement : FindingStatus::Pass, $this->resolveScope($s), RuleRegistry::severity('SEC-ENV-001'), 'Environment file naming', $env ? 'A committed environment file name was detected. This is a potential hygiene risk, not proof that secrets are present.' : 'No obvious committed environment file name was detected.', $env ?: null, $env ? 'Review the file, remove any secrets, and rotate credentials if exposure is confirmed.' : null),
            $this->finding('SEC-POLICY-001', $security, $this->resolveScope($s, '.github', 'docs'), 'Security policy', 'Security policy was detected.', 'Security policy (SECURITY.md) was not detected.', 'Consider adding a SECURITY.md to document security vulnerability reporting procedures.'),
            $this->finding('SEC-DEPENDABOT-001', $dependabot, $this->resolveScope($s, '.github'), 'Dependency update automation', 'Dependabot configuration was detected.', 'Dependabot configuration was not detected.', 'Consider dependency update automation.'),
            $this->finding('SEC-CODEQL-001', $codeql, $this->resolveScope($s, '.github/workflows'), 'CodeQL scanning', 'CodeQL-related workflow detected.', 'CodeQL-related workflow was not detected.', 'Consider enabling CodeQL for security scanning.'),
            new AnalysisFinding('STRUCT-MANIFEST-001', RuleCategory::ProjectStructure, $manifest ? FindingStatus::Pass : FindingStatus::Unknown, FindingScope::RootOnly, RuleRegistry::severity('STRUCT-MANIFEST-001'), 'Dependency/project manifest', 'Dependency/project manifest detected', $manifest ? implode("\n", $manifestFiles) : null, $manifest ? null : 'Add the dependency manifest where applicable.'),
            $this->finding('STRUCT-SOURCE-001', $source, $this->resolveScope($s), 'Source organization', 'A recognizable source directory was detected.', 'A recognizable source directory was not detected. Framework conventions may differ.', 'Use or document the source layout.'),
            $this->finding('GIT-CI-001', $ci, $this->resolveScope($s, '.github/workflows'), 'CI workflow', 'GitHub Actions workflow detected.', 'GitHub Actions workflow was not detected. Another CI provider may be in use.', 'Consider adding a GitHub Actions workflow.'),
            $this->finding('GIT-IGNORE-001', $ignore, $this->resolveScope($s), '.gitignore', 'A .gitignore file was detected.', '.gitignore was not detected.', 'Add a .gitignore appropriate for the project.'),
            $this->finding('CODE-CONFIG-001', $editor, $this->resolveScope($s), 'Editor configuration', 'An editor configuration file was detected.', 'An editor configuration file was not detected.', 'Consider adding shared formatting/editor configuration.'),
        ];
    }

    private function resolveScope(RepositorySnapshot $s, string ...$paths): FindingScope
    {
        foreach ($paths as $path) {
            if ($s->isOmitted($path)) {
                return FindingScope::OmittedBudget;
            }
            if ($this->isUnavailable($s, $path)) {
                return FindingScope::Unavailable;
            }
        }

        return FindingScope::Inspected;
    }

    private function finding(string $rule, ?bool $pass, FindingScope $scope, string $title, string $yes, string $no, string $recommendation): AnalysisFinding
    {
        $status = $pass === null ? FindingStatus::Unknown : ($pass ? FindingStatus::Pass : FindingStatus::Improvement);

        return new AnalysisFinding(
            $rule,
            RuleRegistry::get($rule)->category,
            $status,
            $scope,
            RuleRegistry::severity($rule),
            $title,
            $pass === true ? $yes : ($pass === false ? $no : 'Data required for this check was not detected or was unavailable.'),
            null,
            $pass === true ? null : $recommendation,
        );
    }

    /**
     * Check if a path matches any of the given patterns as a top-level directory or file.
     * Patterns without slash are matched as exact path. Patterns with slash are matched as prefix.
     *
     * @param  list<string>  $patterns
     */
    private function matchAnyPath(RepositorySnapshot $s, array $patterns): bool
    {
        $paths = array_map(strtolower(...), $s->paths);

        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);
            if (str_contains($pattern, '/')) {
                foreach ($paths as $path) {
                    if (str_starts_with($path, $pattern.'/') || $path === $pattern) {
                        return true;
                    }
                }
            } else {
                foreach ($paths as $path) {
                    if ($path === $pattern || str_starts_with($path, $pattern.'/')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /** @param list<string> $keywords */
    private function matchIssueTemplateBasename(string $path, array $keywords): bool
    {
        $basename = strtolower(pathinfo($path, PATHINFO_FILENAME));

        foreach ($keywords as $keyword) {
            if (str_contains($basename, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isUnavailable(RepositorySnapshot $s, string $key): bool
    {
        foreach ($s->unavailableData as $item) {
            if (str_starts_with($item, $key) || $item === $key) {
                return true;
            }
        }

        return false;
    }
}
