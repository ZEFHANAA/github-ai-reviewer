<?php

namespace Tests\Feature;

use App\AI\AIReviewOutcome;
use App\Analysis\AnalysisFinding;
use App\Analysis\AnalysisReport;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use App\Services\Persistence\AnalysisPersistenceService;
use App\ValueObjects\GitHubRepositoryMetadata;
use App\ValueObjects\RepositorySnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysisPersistenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_the_repository_and_its_analysis_scores(): void
    {
        $report = new AnalysisReport(87, ['Documentation' => 100], ['pass' => 1, 'improvement' => 0, 'unknown' => 0], []);

        $analysis = $this->service()->persist($this->snapshot(), $report, AIReviewOutcome::unavailable());

        $this->assertDatabaseHas('repositories', [
            'owner' => 'laravel',
            'name' => 'laravel',
            'full_name' => 'laravel/laravel',
            'url' => 'https://github.com/laravel/laravel',
            'primary_language' => 'PHP',
            'default_branch' => '12.x',
        ]);
        $this->assertDatabaseHas('analyses', [
            'id' => $analysis->id,
            'repository_id' => $analysis->repository_id,
            'overall_score' => 87,
            'github_commit_sha' => null,
        ]);
        $this->assertSame(['Documentation' => 100], $analysis->refresh()->category_scores);
        $this->assertSame(['pass' => 1, 'improvement' => 0, 'unknown' => 0], $analysis->summary);
    }

    public function test_it_creates_a_new_analysis_without_duplicating_its_repository(): void
    {
        $report = new AnalysisReport(87, ['Documentation' => 100], ['pass' => 1, 'improvement' => 0, 'unknown' => 0], []);
        $service = $this->service();
        $snapshot = $this->snapshot();

        $first = $service->persist($snapshot, $report, AIReviewOutcome::unavailable());
        $second = $service->persist($snapshot, $report, AIReviewOutcome::unavailable());

        $this->assertDatabaseCount('repositories', 1);
        $this->assertDatabaseCount('analyses', 2);
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->repository_id, $second->repository_id);
    }

    public function test_it_persists_deterministic_findings_as_analysis_snapshots(): void
    {
        $finding = new AnalysisFinding(
            'DOC-README-001',
            RuleCategory::Documentation,
            FindingStatus::Pass,
            FindingScope::Inspected,
            FindingSeverity::Info,
            'README',
            'README documentation was detected.',
            null,
            null,
        );
        $report = new AnalysisReport(87, ['Documentation' => 100], ['pass' => 1, 'improvement' => 0, 'unknown' => 0], [$finding]);

        $analysis = $this->service()->persist($this->snapshot(), $report, AIReviewOutcome::unavailable());

        $this->assertDatabaseHas('findings', [
            'analysis_id' => $analysis->id,
            'source' => 'rule',
            'rule_identifier' => 'DOC-README-001',
            'category' => 'Documentation',
            'severity' => 'info',
            'status' => 'pass',
            'title' => 'README',
            'message' => 'README documentation was detected.',
        ]);
    }

    public function test_it_marks_analysis_partial_when_ai_is_unavailable_without_ai_findings(): void
    {
        $report = new AnalysisReport(87, ['Documentation' => 100], ['pass' => 1, 'improvement' => 0, 'unknown' => 0], []);

        $analysis = $this->service()->persist($this->snapshot(), $report, AIReviewOutcome::unavailable());

        $this->assertDatabaseHas('analyses', ['id' => $analysis->id, 'status' => 'partial']);
        $this->assertDatabaseMissing('findings', ['analysis_id' => $analysis->id, 'source' => 'ai']);
    }

    private function service(): AnalysisPersistenceService
    {
        return app(AnalysisPersistenceService::class);
    }

    private function snapshot(): RepositorySnapshot
    {
        $contents = file_get_contents(base_path('tests/Fixtures/github-repository.json'));
        $payload = json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);

        return new RepositorySnapshot(GitHubRepositoryMetadata::fromGitHubResponse($payload), ['README.md']);
    }
}
