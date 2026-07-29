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
        $contributing = $s->starts('contributing');
        $tests = $s->starts('tests/') || $s->starts('test/') || $s->starts('spec/') || $s->starts('__tests__/');
        $testConfig = $s->has('phpunit.xml') || $s->has('pest.php') || $s->has('jest.config.js') || $s->has('vitest.config.js') || $s->has('pytest.ini');
        $env = collect($s->paths)->first(fn ($p) => preg_match('#(^|/)\\.env(?:\\..+)?$#i', $p) && ! str_ends_with(strtolower($p), '.example'));
        $dependabot = $s->has('.github/dependabot.yml') || $s->has('.github/dependabot.yaml');
        $manifest = collect(['composer.json', 'package.json', 'pyproject.toml', 'requirements.txt', 'go.mod', 'cargo.toml'])->contains(fn ($p) => $s->has($p));
        $source = $s->starts('app/') || $s->starts('src/') || $s->starts('lib/');
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

    private function check(string $rule, string $category, bool $pass, string $title, string $yes, string $no, string $recommendation): AnalysisFinding
    {
        return new AnalysisFinding($rule, $category, $pass ? 'pass' : 'improvement', $title, $pass ? $yes : $no, null, $pass ? null : $recommendation);
    }
}
