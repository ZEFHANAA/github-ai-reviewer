<?php

namespace Tests\Unit\AI;

use App\AI\AIReviewOutcome;
use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Analysis\AnalysisFinding;
use App\Analysis\AnalysisReport;
use App\Analysis\FinalScoreCalculator;
use App\Contracts\AIReviewProviderInterface;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use App\Services\AI\AIReviewPromptBuilder;
use App\Services\AI\AIReviewResponseValidator;
use App\Services\AI\AIReviewService;
use App\Services\AI\SafeAIReviewService;
use App\Support\SecretRedactor;
use App\ValueObjects\GitHubRepositoryMetadata;
use Error;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class SafeAIReviewServiceTest extends TestCase
{
    public function test_a_valid_provider_response_produces_an_available_outcome(): void
    {
        $response = new AIReviewResponse('Summary.', ['Docs.'], ['Maintainable.'], ['Concern.'], ['Recommendation.']);
        $outcome = $this->safe($this->providerReturning($response))->review($this->metadata(), $this->report());

        $this->assertTrue($outcome->isAvailable);
        $this->assertSame($response->toArray(), $outcome->response?->toArray());
        $this->assertNull($outcome->failureReason);
    }

    public function test_a_provider_exception_produces_an_unavailable_outcome_without_throwing(): void
    {
        $logger = new RecordingLogger;
        $outcome = $this->safe($this->providerThrowing(new Exception('token=ghp_abcdefghijklmnopqrstuvwxyz0123456789 endpoint=https://example.test')), $logger)
            ->review($this->metadata(), $this->report());

        $this->assertFalse($outcome->isAvailable);
        $this->assertNull($outcome->response);
        $this->assertSame('AI review is temporarily unavailable.', $outcome->failureReason);
        $this->assertCount(1, $logger->records);
        $this->assertSame('token=[redacted] endpoint=https://example.test', $logger->records[0]['context']['error']);
        $this->assertArrayNotHasKey('exception', $logger->records[0]['context']);
    }

    public function test_failure_logs_are_informative_without_exception_data_or_any_configured_secret(): void
    {
        $logger = new RecordingLogger;
        $secret = 'future-provider-secret';
        $outcome = $this->safe(
            $this->providerThrowing(new Exception("Bearer {$secret}; token={$secret}; trace should not be logged")),
            $logger,
            new SecretRedactor([$secret]),
        )->review($this->metadata(), $this->report());

        $this->assertFalse($outcome->isAvailable);
        $record = $logger->records[0];
        $serialized = json_encode($record, JSON_THROW_ON_ERROR);

        $this->assertSame(Exception::class, $record['context']['exception_class']);
        $this->assertStringContainsString('Bearer [redacted]', $record['context']['error']);
        $this->assertStringNotContainsString($secret, $serialized);
        $this->assertArrayNotHasKey('exception', $record['context']);
        $this->assertArrayNotHasKey('trace', $record['context']);
    }

    public function test_a_provider_error_produces_an_unavailable_outcome_without_throwing(): void
    {
        $outcome = $this->safe($this->providerThrowing(new Error('provider type failure')))
            ->review($this->metadata(), $this->report());

        $this->assertFalse($outcome->isAvailable);
        $this->assertSame('AI review is temporarily unavailable.', $outcome->failureReason);
    }

    public function test_an_invalid_provider_response_produces_an_unavailable_outcome(): void
    {
        $outcome = $this->safe($this->providerReturning(new AIReviewResponse(' ', [], [], [], [])))
            ->review($this->metadata(), $this->report());

        $this->assertFalse($outcome->isAvailable);
        $this->assertNull($outcome->response);
        $this->assertSame('AI review is temporarily unavailable.', $outcome->failureReason);
    }

    public function test_provider_failure_does_not_change_deterministic_report(): void
    {
        $report = $this->report();
        $before = $report->toArray();

        $outcome = $this->safe($this->providerThrowing(new Exception('offline')))->review($this->metadata(), $report);

        $this->assertFalse($outcome->isAvailable);
        $this->assertSame($before, $report->toArray());
        $this->assertSame($before['final_score'], $report->finalScore);
        $this->assertSame($before['category_scores'], $report->categoryScores);
        $this->assertSame($before['findings'], $report->findings);
    }

    public function test_outcome_factories_enforce_available_and_unavailable_states(): void
    {
        $response = new AIReviewResponse('Summary.', [], [], [], []);

        $available = AIReviewOutcome::available($response);
        $unavailable = AIReviewOutcome::unavailable();

        $this->assertTrue($available->isAvailable);
        $this->assertSame($response, $available->response);
        $this->assertNull($available->failureReason);
        $this->assertFalse($unavailable->isAvailable);
        $this->assertNull($unavailable->response);
        $this->assertSame('AI review is temporarily unavailable.', $unavailable->failureReason);
    }

    public function test_outcome_exposes_view_data_without_provider_logic_in_the_view(): void
    {
        $response = new AIReviewResponse('Summary.', ['Docs.'], ['Maintainable.'], ['Concern.'], ['Recommendation.']);

        $available = AIReviewOutcome::available($response);
        $unavailable = AIReviewOutcome::unavailable();

        $this->assertSame('AI-assisted qualitative review', $available->sourceLabel);
        $this->assertNull($available->notice);
        $this->assertSame([
            ['title' => 'Repository Summary', 'entries' => ['Summary.']],
            ['title' => 'Documentation Observations', 'entries' => ['Docs.']],
            ['title' => 'Maintainability Observations', 'entries' => ['Maintainable.']],
            ['title' => 'Potential Concerns', 'entries' => ['Concern.']],
            ['title' => 'Prioritized Recommendations', 'entries' => ['Recommendation.']],
        ], $available->sections);
        $this->assertSame('AI enrichment unavailable', $unavailable->sourceLabel);
        $this->assertSame('AI review is temporarily unavailable.', $unavailable->notice);
        $this->assertSame([], $unavailable->sections);
    }

    private function safe(AIReviewProviderInterface $provider, ?RecordingLogger $logger = null, ?SecretRedactor $redactor = null): SafeAIReviewService
    {
        return new SafeAIReviewService(
            new AIReviewService($provider, new AIReviewPromptBuilder),
            new AIReviewResponseValidator,
            $logger ?? new RecordingLogger,
            $redactor ?? new SecretRedactor
        );
    }

    private function providerReturning(AIReviewResponse $response): AIReviewProviderInterface
    {
        return new class($response) implements AIReviewProviderInterface
        {
            public function __construct(private AIReviewResponse $response) {}

            public function review(AIReviewRequest $request): AIReviewResponse
            {
                return $this->response;
            }
        };
    }

    private function providerThrowing(\Throwable $exception): AIReviewProviderInterface
    {
        return new class($exception) implements AIReviewProviderInterface
        {
            public function __construct(private \Throwable $exception) {}

            public function review(AIReviewRequest $request): AIReviewResponse
            {
                throw $this->exception;
            }
        };
    }

    private function metadata(): GitHubRepositoryMetadata
    {
        return GitHubRepositoryMetadata::fromGitHubResponse([
            'full_name' => 'acme/project', 'html_url' => 'https://github.com/acme/project',
            'owner' => ['login' => 'acme'], 'name' => 'project', 'description' => null,
            'default_branch' => 'main', 'language' => 'PHP', 'stargazers_count' => 0,
            'forks_count' => 0, 'open_issues_count' => 0, 'watchers_count' => 0,
            'subscribers_count' => null, 'size' => 1, 'created_at' => null, 'updated_at' => null,
            'pushed_at' => null, 'archived' => false, 'fork' => false, 'visibility' => 'public',
            'license' => null, 'topics' => [],
        ]);
    }

    private function report(): AnalysisReport
    {
        return (new FinalScoreCalculator)->report([
            new AnalysisFinding('DOC-README-001', RuleCategory::Documentation, FindingStatus::Pass, FindingScope::Inspected, FindingSeverity::Medium, 'README', 'Found.'),
        ]);
    }
}

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string|\Stringable, context: array<mixed>}> */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = compact('level', 'message', 'context');
    }
}
