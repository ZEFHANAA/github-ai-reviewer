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

    public function test_every_emitted_string_begins_with_a_real_deterministic_rule_id(): void
    {
        $response = (new FakeAIReviewProvider)->review($this->request($this->findings()));
        $all = [$response->repositorySummary, ...$response->documentationObservations, ...$response->maintainabilityObservations, ...$response->potentialConcerns, ...$response->prioritizedRecommendations];

        foreach ($all as $line) {
            $this->assertMatchesRegularExpression(
                '/^\[[A-Z]+-[A-Z]+-\d+\]\s/u',
                $line,
                "Fake provider output must start with a rule ID: {$line}"
            );
        }
    }

    public function test_review_uses_exact_fallback_strings_with_rule_references_when_no_finding_matches(): void
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

        $this->assertStringStartsWith('[GIT-CI-001]', $response->documentationObservations[0]);
        $this->assertStringStartsWith('[GIT-CI-001]', $response->potentialConcerns[0]);
        $this->assertStringStartsWith('[GIT-CI-001]', $response->maintainabilityObservations[0]);
        $this->assertStringStartsWith('[GIT-CI-001]', $response->prioritizedRecommendations[0]);
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

        $this->assertStringStartsWith('[SEC-ENV-001]', $response->documentationObservations[0]);
        $this->assertStringStartsWith('[SEC-ENV-001]', $response->potentialConcerns[0]);
        $this->assertStringContainsString('Environment file', $response->potentialConcerns[0]);
        $this->assertStringContainsString('Environment file detected.', $response->potentialConcerns[0]);
    }

    private function request(array $findings): AIReviewRequest
    {
        return new AIReviewRequest(
            $this->metadata(),
            (new FinalScoreCalculator)->report($findings),
            'prompt is unused by the fake provider'
        );
    }

    /** @return list<AnalysisFinding> */
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

    private function metadata(): GitHubRepositoryMetadata
    {
        return GitHubRepositoryMetadata::fromGitHubResponse([
            'full_name' => 'laravel/laravel',
            'html_url' => 'https://github.com/laravel/laravel',
            'owner' => ['login' => 'laravel'],
            'name' => 'laravel',
            'description' => null,
            'default_branch' => 'main',
            'language' => 'PHP',
            'stargazers_count' => 25000,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'watchers_count' => 0,
            'subscribers_count' => null,
            'size' => 1,
            'created_at' => null,
            'updated_at' => null,
            'pushed_at' => null,
            'archived' => false,
            'fork' => false,
            'visibility' => 'public',
            'license' => null,
            'topics' => [],
        ]);
    }
}
