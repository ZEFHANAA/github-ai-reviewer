<?php

namespace Tests\Unit\AI;

use App\AI\AIReviewRequest;
use App\Analysis\AnalysisFinding;
use App\Analysis\FinalScoreCalculator;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use App\Services\AI\Providers\FakeAIReviewProvider;
use App\ValueObjects\GitHubRepositoryMetadata;
use Tests\TestCase;

class FakeAIReviewProviderTest extends TestCase
{
    public function test_identical_requests_produce_byte_identical_responses(): void
    {
        $provider = new FakeAIReviewProvider;
        $request = $this->request($this->findings());

        $this->assertSame(
            $provider->review($request)->toArray(),
            $provider->review($request)->toArray()
        );
    }

    public function test_the_response_exposes_exactly_the_allowed_contract_keys(): void
    {
        $response = (new FakeAIReviewProvider)->review($this->request($this->findings()));

        $this->assertSame(
            [
                'repository_summary',
                'documentation_observations',
                'maintainability_observations',
                'potential_concerns',
                'prioritized_recommendations',
            ],
            array_keys($response->toArray())
        );
    }

    public function test_the_repository_summary_is_built_from_metadata_exactly(): void
    {
        // Fixture: full_name=laravel/laravel, language=PHP, stargazers_count=25000.
        $response = (new FakeAIReviewProvider)->review($this->request($this->findings()));

        $this->assertSame(
            'laravel/laravel is a PHP repository with 25000 stars.',
            $response->repositorySummary
        );
    }

    public function test_review_uses_exact_fallback_strings_when_no_finding_matches_any_observation(): void
    {
        $response = (new FakeAIReviewProvider)->review($this->request([
            new AnalysisFinding(
                'GIT-CI-001',
                RuleCategory::GitPractices,
                FindingStatus::Pass,
                FindingScope::Inspected,
                FindingSeverity::Low,
                'CI workflow',
                'Workflow detected.'
            ),
        ]));

        $this->assertSame([
            'No documentation findings to report.',
        ], $response->documentationObservations);
        $this->assertSame([
            'No concerns flagged by deterministic checks.',
        ], $response->potentialConcerns);
        $this->assertSame([
            'All inspected checks produced a verdict.',
        ], $response->maintainabilityObservations);
        $this->assertSame([
            'No deterministic improvements pending for laravel/laravel.',
        ], $response->prioritizedRecommendations);
    }

    public function test_review_filters_non_documentation_and_empty_recommendation_improvements(): void
    {
        $response = (new FakeAIReviewProvider)->review($this->request([
            new AnalysisFinding(
                'SEC-ENV-001',
                RuleCategory::SecurityHygiene,
                FindingStatus::Improvement,
                FindingScope::Inspected,
                FindingSeverity::High,
                'Environment file',
                'Environment file detected.',
                '.env',
                ''
            ),
        ]));

        $this->assertSame([
            'No documentation findings to report.',
        ], $response->documentationObservations);
        $this->assertSame([
            'SEC-ENV-001: Environment file — Environment file detected.',
        ], $response->potentialConcerns);
        $this->assertSame([
            'All inspected checks produced a verdict.',
        ], $response->maintainabilityObservations);
        $this->assertSame([
            'No deterministic improvements pending for laravel/laravel.',
        ], $response->prioritizedRecommendations);
    }

    private function request(array $findings): AIReviewRequest
    {
        return new AIReviewRequest(
            $this->metadata(),
            (new FinalScoreCalculator)->report($findings),
            'prompt is unused by the fake provider'
        );
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
                FindingStatus::Improvement,
                FindingScope::Inspected,
                FindingSeverity::Medium,
                'README missing',
                'README was not detected.',
                null,
                'Add a README explaining setup and usage.'
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
