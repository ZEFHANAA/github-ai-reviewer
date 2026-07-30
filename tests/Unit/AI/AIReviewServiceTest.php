<?php

namespace Tests\Unit\AI;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Analysis\AnalysisFinding;
use App\Analysis\FinalScoreCalculator;
use App\Contracts\AIReviewProviderInterface;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use App\Services\AI\AIReviewPromptBuilder;
use App\Services\AI\AIReviewService;
use App\Services\AI\Providers\FakeAIReviewProvider;
use App\Services\AI\SafeAIReviewService;
use App\ValueObjects\GitHubRepositoryMetadata;
use Tests\TestCase;

class AIReviewServiceTest extends TestCase
{
    public function test_it_returns_a_response_with_every_allowed_section(): void
    {
        $response = $this->service()->review($this->metadata(), (new FinalScoreCalculator)->report($this->findings()));

        $this->assertInstanceOf(AIReviewResponse::class, $response);
        $this->assertNotSame('', $response->repositorySummary);
        $this->assertNotEmpty($response->documentationObservations);
        $this->assertNotEmpty($response->maintainabilityObservations);
        $this->assertNotEmpty($response->potentialConcerns);
        $this->assertNotEmpty($response->prioritizedRecommendations);
    }

    public function test_it_passes_the_built_request_to_the_provider(): void
    {
        $provider = new class implements AIReviewProviderInterface
        {
            public ?AIReviewRequest $received = null;

            public function review(AIReviewRequest $request): AIReviewResponse
            {
                $this->received = $request;

                return new AIReviewResponse('summary', ['doc'], ['maint'], ['concern'], ['rec']);
            }
        };

        $metadata = $this->metadata();
        $report = (new FinalScoreCalculator)->report($this->findings());

        (new AIReviewService($provider, new AIReviewPromptBuilder))->review($metadata, $report);

        $this->assertInstanceOf(AIReviewRequest::class, $provider->received);
        $this->assertSame($metadata, $provider->received->metadata);
        $this->assertSame($report, $provider->received->report);
        $this->assertStringContainsString('laravel/laravel', $provider->received->prompt);
    }

    public function test_it_does_not_change_deterministic_findings_or_scores(): void
    {
        $report = (new FinalScoreCalculator)->report($this->findings());
        $before = $report->toArray();

        $this->service()->review($this->metadata(), $report);

        $this->assertSame($before, $report->toArray());
        $this->assertSame($before['final_score'], $report->finalScore);
        $this->assertSame($before['category_scores'], $report->categoryScores);
        $this->assertSame($before['findings'], $report->findings);
    }

    public function test_the_response_exposes_no_scores(): void
    {
        $response = $this->service()->review($this->metadata(), (new FinalScoreCalculator)->report($this->findings()));

        $this->assertSame(
            ['repository_summary', 'documentation_observations', 'maintainability_observations', 'potential_concerns', 'prioritized_recommendations'],
            array_keys($response->toArray())
        );
    }

    public function test_the_fake_provider_is_deterministic(): void
    {
        $report = (new FinalScoreCalculator)->report($this->findings());

        $this->assertSame(
            $this->service()->review($this->metadata(), $report)->toArray(),
            $this->service()->review($this->metadata(), $report)->toArray()
        );
    }

    public function test_the_fake_provider_reflects_deterministic_input_without_scoring_it(): void
    {
        $response = $this->service()->review($this->metadata(), (new FinalScoreCalculator)->report($this->findings()));
        $serialized = json_encode($response->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('laravel/laravel', $serialized);
        $this->assertStringContainsString('SEC-ENV-001', $serialized);
        $this->assertStringNotContainsString('DOC-README-001', $serialized);
    }

    public function test_the_service_is_resolvable_from_the_container(): void
    {
        $this->assertInstanceOf(FakeAIReviewProvider::class, $this->app->make(AIReviewProviderInterface::class));
        $this->assertInstanceOf(AIReviewService::class, $this->app->make(AIReviewService::class));
    }

    public function test_the_safe_decorator_is_resolvable_from_the_container(): void
    {
        $safe = $this->app->make(SafeAIReviewService::class);

        $this->assertInstanceOf(SafeAIReviewService::class, $safe);
        $this->assertTrue(
            $safe->review($this->metadata(), (new FinalScoreCalculator)->report($this->findings()))->isAvailable
        );
    }

    private function service(): AIReviewService
    {
        return new AIReviewService(new FakeAIReviewProvider, new AIReviewPromptBuilder);
    }

    private function metadata(): GitHubRepositoryMetadata
    {
        $payload = json_decode(
            file_get_contents(base_path('tests/Fixtures/github-repository.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return GitHubRepositoryMetadata::fromGitHubResponse($payload);
    }

    /**
     * @return list<AnalysisFinding>
     */
    private function findings(): array
    {
        return [
            new AnalysisFinding(
                'DOC-README-001',
                RuleCategory::Documentation,
                FindingStatus::Pass,
                FindingScope::Inspected,
                FindingSeverity::Medium,
                'README present',
                'A README file was found.'
            ),
            new AnalysisFinding(
                'SEC-ENV-001',
                RuleCategory::SecurityHygiene,
                FindingStatus::Improvement,
                FindingScope::Inspected,
                FindingSeverity::High,
                'Committed .env file',
                'A tracked .env file was detected.',
                '.env',
                'Remove the file and rotate any exposed credentials.'
            ),
            new AnalysisFinding(
                'GIT-CI-001',
                RuleCategory::GitPractices,
                FindingStatus::Unknown,
                FindingScope::Unavailable,
                FindingSeverity::Low,
                'CI workflows not inspected',
                'Workflow directory could not be read.'
            ),
        ];
    }
}
