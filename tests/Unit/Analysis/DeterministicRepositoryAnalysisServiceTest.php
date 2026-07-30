<?php

namespace Tests\Unit\Analysis;

use App\Analysis\AnalysisFinding;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Services\Analysis\DeterministicRepositoryAnalysisService;
use App\ValueObjects\GitHubRepositoryMetadata;
use App\ValueObjects\RepositorySnapshot;
use Tests\TestCase;

class DeterministicRepositoryAnalysisServiceTest extends TestCase
{
    private function snapshot(array $paths, array $unavailable = []): RepositorySnapshot
    {
        $payload = json_decode(file_get_contents(base_path('tests/Fixtures/github-repository.json')), true, 512, JSON_THROW_ON_ERROR);

        return new RepositorySnapshot(GitHubRepositoryMetadata::fromGitHubResponse($payload), $paths, $unavailable);
    }

    public function test_findings_use_closed_status_scope_and_severity_enums(): void
    {
        $findings = (new DeterministicRepositoryAnalysisService)->analyze($this->snapshot(['.env', 'composer.json']));

        foreach ($findings as $finding) {
            $this->assertInstanceOf(AnalysisFinding::class, $finding);
            $this->assertInstanceOf(FindingStatus::class, $finding->status);
            $this->assertInstanceOf(FindingScope::class, $finding->scope);
            $this->assertInstanceOf(FindingSeverity::class, $finding->severity);
        }

        $environment = collect($findings)->firstWhere('ruleIdentifier', 'SEC-ENV-001');
        $this->assertSame(FindingStatus::Improvement, $environment->status);
        $this->assertSame(FindingSeverity::High, $environment->severity);
    }

    public function test_manifest_finding_has_consistent_contract(): void
    {
        $finding = collect((new DeterministicRepositoryAnalysisService)->analyze($this->snapshot(['composer.json'])))->firstWhere('ruleIdentifier', 'STRUCT-MANIFEST-001');

        $this->assertSame(FindingStatus::Pass, $finding->status);
        $this->assertSame(FindingScope::RootOnly, $finding->scope);
        $this->assertNull($finding->recommendation);
    }

    public function test_it_detects_test_directories(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['tests'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['test'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['spec'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['specs'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
    }

    public function test_it_returns_unknown_for_missing_test_directories(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Unknown, collect($service->analyze($this->snapshot(['src'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
    }

    public function test_it_detects_contribution_guidance_in_common_locations(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['CONTRIBUTING'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['CONTRIBUTING.md'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/CONTRIBUTING.md'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['docs/CONTRIBUTING.md'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
    }

    public function test_it_returns_unknown_for_contribution_if_folder_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        // If docs is unavailable, we don't know if contributing exists there, so return unknown instead of improvement
        $this->assertEquals(FindingStatus::Unknown, collect($service->analyze($this->snapshot([], ['docs'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
    }

    public function test_it_detects_community_and_governance_files(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([
            'SECURITY.md',
            'CODE_OF_CONDUCT.md',
            'CHANGELOG.md',
        ]));

        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'SEC-POLICY-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'DOC-CONDUCT-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'DOC-CHANGELOG-001')->status);
    }

    public function test_it_detects_community_files_in_standard_subdirectories(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([
            '.github/SECURITY.md',
            'docs/CODE_OF_CONDUCT.md',
            'docs/CHANGELOG.md',
        ]));

        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'SEC-POLICY-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'DOC-CONDUCT-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'DOC-CHANGELOG-001')->status);
    }

    public function test_it_returns_unknown_for_community_files_if_standard_directories_are_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([], ['.github', 'docs']));

        $this->assertEquals(FindingStatus::Unknown, collect($findings)->firstWhere('ruleIdentifier', 'SEC-POLICY-001')->status);
        $this->assertEquals(FindingStatus::Unknown, collect($findings)->firstWhere('ruleIdentifier', 'DOC-CONDUCT-001')->status);
        $this->assertEquals(FindingStatus::Unknown, collect($findings)->firstWhere('ruleIdentifier', 'DOC-CHANGELOG-001')->status);
    }

    public function test_it_detects_github_community_health_templates(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([
            '.github/ISSUE_TEMPLATE',
            '.github/ISSUE_TEMPLATE/bug_report.md',
            '.github/ISSUE_TEMPLATE/feature_request.yml',
            '.github/PULL_REQUEST_TEMPLATE.md',
        ]));

        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'COMM-BUG-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'COMM-FEATURE-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($findings)->firstWhere('ruleIdentifier', 'COMM-PR-001')->status);
    }

    public function test_it_returns_improvement_when_github_is_available_but_templates_are_missing(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot(['.github']));

        $this->assertEquals(FindingStatus::Improvement, collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->status);
        $this->assertEquals(FindingStatus::Improvement, collect($findings)->firstWhere('ruleIdentifier', 'COMM-BUG-001')->status);
        $this->assertEquals(FindingStatus::Improvement, collect($findings)->firstWhere('ruleIdentifier', 'COMM-FEATURE-001')->status);
        $this->assertEquals(FindingStatus::Improvement, collect($findings)->firstWhere('ruleIdentifier', 'COMM-PR-001')->status);
    }

    public function test_it_returns_unknown_for_github_templates_if_github_is_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([], ['.github']));

        $this->assertEquals(FindingStatus::Unknown, collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->status);
        $this->assertEquals(FindingStatus::Unknown, collect($findings)->firstWhere('ruleIdentifier', 'COMM-BUG-001')->status);
        $this->assertEquals(FindingStatus::Unknown, collect($findings)->firstWhere('ruleIdentifier', 'COMM-FEATURE-001')->status);
        $this->assertEquals(FindingStatus::Unknown, collect($findings)->firstWhere('ruleIdentifier', 'COMM-PR-001')->status);
    }

    public function test_it_detects_dependabot_configuration(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/dependabot.yml'])))->firstWhere('ruleIdentifier', 'SEC-DEPENDABOT-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/dependabot.yaml'])))->firstWhere('ruleIdentifier', 'SEC-DEPENDABOT-001')->status);
    }

    public function test_it_returns_unknown_for_dependabot_if_github_is_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Unknown, collect($service->analyze($this->snapshot([], ['.github'])))->firstWhere('ruleIdentifier', 'SEC-DEPENDABOT-001')->status);
    }

    public function test_it_detects_github_actions_workflow_files(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/workflows/ci.yml'])))->firstWhere('ruleIdentifier', 'GIT-CI-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/workflows/deploy.yaml'])))->firstWhere('ruleIdentifier', 'GIT-CI-001')->status);
    }

    public function test_it_returns_unknown_for_ci_if_workflows_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Unknown, collect($service->analyze($this->snapshot([], ['.github/workflows'])))->firstWhere('ruleIdentifier', 'GIT-CI-001')->status);
    }

    public function test_it_returns_improvement_for_ci_if_workflows_inspected_but_absent(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Improvement, collect($service->analyze($this->snapshot(['.github/workflows'])))->firstWhere('ruleIdentifier', 'GIT-CI-001')->status);
    }

    public function test_it_detects_codeql_workflow_by_filename(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/workflows/codeql.yml'])))->firstWhere('ruleIdentifier', 'SEC-CODEQL-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/workflows/codeql.yaml'])))->firstWhere('ruleIdentifier', 'SEC-CODEQL-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/workflows/codeql-analysis.yml'])))->firstWhere('ruleIdentifier', 'SEC-CODEQL-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['.github/workflows/codeql-analysis.yaml'])))->firstWhere('ruleIdentifier', 'SEC-CODEQL-001')->status);
    }

    public function test_it_returns_unknown_for_codeql_if_workflows_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Unknown, collect($service->analyze($this->snapshot([], ['.github/workflows'])))->firstWhere('ruleIdentifier', 'SEC-CODEQL-001')->status);
    }

    public function test_it_returns_improvement_for_codeql_if_workflows_inspected_but_absent(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Improvement, collect($service->analyze($this->snapshot(['.github/workflows/ci.yml'])))->firstWhere('ruleIdentifier', 'SEC-CODEQL-001')->status);
    }

    public function test_it_detects_source_organization(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['src'])))->firstWhere('ruleIdentifier', 'STRUCT-SOURCE-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['app'])))->firstWhere('ruleIdentifier', 'STRUCT-SOURCE-001')->status);
        $this->assertEquals(FindingStatus::Pass, collect($service->analyze($this->snapshot(['lib'])))->firstWhere('ruleIdentifier', 'STRUCT-SOURCE-001')->status);
    }

    public function test_it_detects_dependency_manifests_and_reports_all_ecosystems(): void
    {
        $service = new DeterministicRepositoryAnalysisService;

        foreach ([
            'composer.json', 'package.json', 'pyproject.toml', 'Cargo.toml', 'go.mod',
            'pom.xml', 'build.gradle.kts', 'Gemfile', 'App.csproj', 'Solution.sln', 'pubspec.yaml',
        ] as $manifest) {
            $finding = collect($service->analyze($this->snapshot([$manifest])))->firstWhere('ruleIdentifier', 'STRUCT-MANIFEST-001');
            $this->assertEquals(FindingStatus::Pass, $finding->status, $manifest);
            $this->assertSame($manifest, $finding->evidence, $manifest);
        }

        $finding = collect($service->analyze($this->snapshot(['composer.json', 'package.json'])))->firstWhere('ruleIdentifier', 'STRUCT-MANIFEST-001');
        $this->assertEquals(FindingStatus::Pass, $finding->status);
        $this->assertSame("composer.json\npackage.json", $finding->evidence);
    }

    public function test_it_returns_unknown_for_an_unrecognized_manifest_set(): void
    {
        $finding = collect((new DeterministicRepositoryAnalysisService)->analyze($this->snapshot(['random.txt'])))->firstWhere('ruleIdentifier', 'STRUCT-MANIFEST-001');

        $this->assertEquals(FindingStatus::Unknown, $finding->status);
        $this->assertNull($finding->evidence);
    }

    public function test_it_does_not_read_manifest_contents_or_subdirectories(): void
    {
        $finding = collect((new DeterministicRepositoryAnalysisService)->analyze($this->snapshot(['apps/api/composer.json'])))->firstWhere('ruleIdentifier', 'STRUCT-MANIFEST-001');

        $this->assertEquals(FindingStatus::Unknown, $finding->status);
    }

    public function test_omitted_budget_scope_is_preserved(): void
    {
        $payload = json_decode(file_get_contents(base_path('tests/Fixtures/github-repository.json')), true, 512, JSON_THROW_ON_ERROR);
        $finding = collect((new DeterministicRepositoryAnalysisService)->analyze(
            new RepositorySnapshot(
                GitHubRepositoryMetadata::fromGitHubResponse($payload),
                ['.github', 'docs'],
                [],
                ['docs'],
            )
        ))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001');

        $this->assertSame(FindingScope::OmittedBudget, $finding->scope);
    }

    public function test_root_only_scope_for_manifest(): void
    {
        $finding = collect((new DeterministicRepositoryAnalysisService)->analyze($this->snapshot(['composer.json', 'package.json'])))->firstWhere('ruleIdentifier', 'STRUCT-MANIFEST-001');
        $this->assertSame(FindingScope::RootOnly, $finding->scope);
    }

    public function test_unavailable_scope_when_github_unavailable(): void
    {
        $findings = (new DeterministicRepositoryAnalysisService)->analyze($this->snapshot([], ['.github']));

        $this->assertSame(FindingScope::Unavailable, collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->scope);
        $this->assertSame(FindingScope::Unavailable, collect($findings)->firstWhere('ruleIdentifier', 'SEC-DEPENDABOT-001')->scope);
    }

    public function test_unavailable_scope_when_workflows_unavailable(): void
    {
        $findings = (new DeterministicRepositoryAnalysisService)->analyze($this->snapshot([], ['.github/workflows']));

        $this->assertSame(FindingScope::Unavailable, collect($findings)->firstWhere('ruleIdentifier', 'GIT-CI-001')->scope);
        $this->assertSame(FindingScope::Unavailable, collect($findings)->firstWhere('ruleIdentifier', 'SEC-CODEQL-001')->scope);
    }

    public function test_inspected_scope_when_standard_dirs_available(): void
    {
        $findings = (new DeterministicRepositoryAnalysisService)->analyze($this->snapshot(['.github', 'docs']));

        $this->assertSame(FindingScope::Inspected, collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->scope);
        $this->assertSame(FindingScope::Inspected, collect($findings)->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->scope);
    }
}
