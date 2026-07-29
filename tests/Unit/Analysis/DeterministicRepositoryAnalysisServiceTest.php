<?php

namespace Tests\Unit\Analysis;

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

    public function test_it_detects_test_directories(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['tests'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['test'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['spec'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['specs'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
    }

    public function test_it_returns_unknown_for_missing_test_directories(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals('unknown', collect($service->analyze($this->snapshot(['src'])))->firstWhere('ruleIdentifier', 'TEST-DIRECTORY-001')->status);
    }

    public function test_it_detects_contribution_guidance_in_common_locations(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['CONTRIBUTING'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['CONTRIBUTING.md'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['.github/CONTRIBUTING.md'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['docs/CONTRIBUTING.md'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
    }

    public function test_it_returns_unknown_for_contribution_if_folder_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        // If docs is unavailable, we don't know if contributing exists there, so return unknown instead of improvement
        $this->assertEquals('unknown', collect($service->analyze($this->snapshot([], ['docs'])))->firstWhere('ruleIdentifier', 'DOC-CONTRIBUTING-001')->status);
    }

    public function test_it_detects_community_and_governance_files(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([
            'SECURITY.md',
            'CODE_OF_CONDUCT.md',
            'CHANGELOG.md',
        ]));

        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'SEC-POLICY-001')->status);
        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'DOC-CONDUCT-001')->status);
        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'DOC-CHANGELOG-001')->status);
    }

    public function test_it_detects_community_files_in_standard_subdirectories(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([
            '.github/SECURITY.md',
            'docs/CODE_OF_CONDUCT.md',
            'docs/CHANGELOG.md',
        ]));

        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'SEC-POLICY-001')->status);
        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'DOC-CONDUCT-001')->status);
        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'DOC-CHANGELOG-001')->status);
    }

    public function test_it_returns_unknown_for_community_files_if_standard_directories_are_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([], ['.github', 'docs']));

        $this->assertEquals('unknown', collect($findings)->firstWhere('ruleIdentifier', 'SEC-POLICY-001')->status);
        $this->assertEquals('unknown', collect($findings)->firstWhere('ruleIdentifier', 'DOC-CONDUCT-001')->status);
        $this->assertEquals('unknown', collect($findings)->firstWhere('ruleIdentifier', 'DOC-CHANGELOG-001')->status);
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

        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->status);
        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'COMM-BUG-001')->status);
        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'COMM-FEATURE-001')->status);
        $this->assertEquals('pass', collect($findings)->firstWhere('ruleIdentifier', 'COMM-PR-001')->status);
    }

    public function test_it_returns_improvement_when_github_is_available_but_templates_are_missing(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot(['.github']));

        $this->assertEquals('improvement', collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->status);
        $this->assertEquals('improvement', collect($findings)->firstWhere('ruleIdentifier', 'COMM-BUG-001')->status);
        $this->assertEquals('improvement', collect($findings)->firstWhere('ruleIdentifier', 'COMM-FEATURE-001')->status);
        $this->assertEquals('improvement', collect($findings)->firstWhere('ruleIdentifier', 'COMM-PR-001')->status);
    }

    public function test_it_returns_unknown_for_github_templates_if_github_is_unavailable(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $findings = $service->analyze($this->snapshot([], ['.github']));

        $this->assertEquals('unknown', collect($findings)->firstWhere('ruleIdentifier', 'COMM-ISSUE-001')->status);
        $this->assertEquals('unknown', collect($findings)->firstWhere('ruleIdentifier', 'COMM-BUG-001')->status);
        $this->assertEquals('unknown', collect($findings)->firstWhere('ruleIdentifier', 'COMM-FEATURE-001')->status);
        $this->assertEquals('unknown', collect($findings)->firstWhere('ruleIdentifier', 'COMM-PR-001')->status);
    }

    public function test_it_detects_source_organization(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['src'])))->firstWhere('ruleIdentifier', 'STRUCT-SOURCE-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['app'])))->firstWhere('ruleIdentifier', 'STRUCT-SOURCE-001')->status);
        $this->assertEquals('pass', collect($service->analyze($this->snapshot(['lib'])))->firstWhere('ruleIdentifier', 'STRUCT-SOURCE-001')->status);
    }

    public function test_it_returns_unknown_for_missing_source_organization(): void
    {
        $service = new DeterministicRepositoryAnalysisService;
        $this->assertEquals('unknown', collect($service->analyze($this->snapshot(['README.md'])))->firstWhere('ruleIdentifier', 'STRUCT-SOURCE-001')->status);
    }
}
