<?php

namespace App\Services\Analysis;

use App\Analysis\AnalysisFinding;
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

        // Rule A: Test detection
        $hasTests = $this->matchAnyPath($s, ['tests', 'test', 'spec', 'specs', '__tests__']);
        // Rule A Requirement: "Do not assume that missing test signals prove that the project has no tests." -> return unknown (null)
        $tests = $hasTests ? true : null;

        $testConfig = $s->has('phpunit.xml') || $s->has('pest.php') || $s->has('jest.config.js') || $s->has('vitest.config.js') || $s->has('pytest.ini');

        $env = collect($s->paths)->first(fn ($p) => preg_match('#(^|/)\\.env(?:\\..+)?$#i', $p) && ! str_ends_with(strtolower($p), '.example'));
        $dependabot = $s->has('.github/dependabot.yml') || $s->has('.github/dependabot.yaml');
        $manifest = collect(['composer.json', 'package.json', 'pyproject.toml', 'requirements.txt', 'go.mod', 'cargo.toml'])->contains(fn ($p) => $s->has($p));

        // Rule C: Source organization
        $hasSource = $this->matchAnyPath($s, ['app', 'src', 'lib']);
        $source = $hasSource ? true : null;

        $ci = $s->starts('.github/workflows/');
        $ignore = $s->has('.gitignore');
        $editor = $s->has('.editorconfig');

        return [
            $this->check('DOC-README-001', 'Documentation', $readme, 'README', 'README documentation was detected.', 'README not detected. This is an improvement opportunity, not proof that documentation is absent elsewhere.', 'Add a README explaining setup and usage.'),
            $this->check('DOC-LICENSE-001', 'Documentation', $license, 'License', 'A license file was detected.', 'License not detected.', 'Add an appropriate license file.'),
            $this->check('DOC-CONTRIBUTING-001', 'Documentation', $contributing, 'Contribution guidance', 'Contribution guidance was detected.', 'Contribution guidance not detected.', 'Consider adding CONTRIBUTING.md.'),
            $this->check('TEST-DIRECTORY-001', 'Testing', $tests, 'Test files', 'Recognizable test paths were detected.', 'Recognizable test paths were not detected. This does not measure coverage or test quality.', 'Add automated tests in a conventional directory.'),
            $this->check('TEST-CONFIG-001', 'Testing', $testConfig, 'Test configuration', 'A recognizable test configuration file was detected.', 'A recognizable test configuration file was not detected.', 'Add or document the test runner configuration.'),
            new AnalysisFinding('SEC-ENV-001', 'Security hygiene', $env ? 'warning' : 'pass', 'Environment file naming', $env ? 'A committed environment file name was detected. This is a potential hygiene risk, not proof that secrets are present.' : 'No obvious committed environment file name was detected.', $env ?: null, $env ? 'Review the file, remove any secrets, and rotate credentials if exposure is confirmed.' : null),
            $this->check('SEC-DEPENDABOT-001', 'Security hygiene', $dependabot, 'Dependency update automation', 'Dependabot configuration was detected.', 'Dependabot configuration was not detected.', 'Consider dependency update automation.'),
            $this->check('STRUCT-DEPS-001', 'Project structure', $manifest, 'Dependency manifest', 'A common dependency manifest was detected.', 'A common dependency manifest was not detected.', 'Add the dependency manifest where applicable.'),
            $this->check('STRUCT-SOURCE-001', 'Project structure', $source, 'Source organization', 'A recognizable source directory was detected.', 'A recognizable source directory was not detected. Framework conventions may differ.', 'Use or document the source layout.'),
            $this->check('GIT-CI-001', 'Git practices', $ci, 'CI workflow', 'GitHub Actions workflow files were detected.', 'GitHub Actions workflows were not detected. Another CI provider may be in use.', 'Add or document CI configuration.'),
            $this->check('GIT-IGNORE-001', 'Git practices', $ignore, '.gitignore', 'A .gitignore file was detected.', '.gitignore was not detected.', 'Add a .gitignore appropriate for the project.'),
            $this->check('CODE-CONFIG-001', 'Code quality', $editor, 'Editor configuration', 'An editor configuration file was detected.', 'An editor configuration file was not detected.', 'Consider adding shared formatting/editor configuration.'),
        ];
    }

    private function check(string $rule, string $category, ?bool $pass, string $title, string $yes, string $no, string $recommendation): AnalysisFinding
    {
        $status = $pass === null ? 'unknown' : ($pass ? 'pass' : 'improvement');

        return new AnalysisFinding(
            $rule,
            $category,
            $status,
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
