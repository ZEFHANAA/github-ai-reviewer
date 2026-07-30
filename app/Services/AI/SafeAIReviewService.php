<?php

namespace App\Services\AI;

use App\AI\AIReviewOutcome;
use App\Analysis\AnalysisReport;
use App\ValueObjects\GitHubRepositoryMetadata;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Failure-isolation decorator: AI review is optional enrichment, so any
 * provider or validation failure becomes an unavailable outcome instead of
 * an exception. Deterministic analysis is never affected. Callers (the
 * controller in Phase 7C) should depend on this class, not AIReviewService.
 */
final readonly class SafeAIReviewService
{
    public function __construct(
        private AIReviewService $service,
        private AIReviewResponseValidator $validator,
        private LoggerInterface $logger,
    ) {}

    public function review(GitHubRepositoryMetadata $metadata, AnalysisReport $report): AIReviewOutcome
    {
        try {
            return AIReviewOutcome::available(
                $this->validator->validate($this->service->review($metadata, $report))
            );
        } catch (Throwable $exception) {
            $this->logger->warning('AI review failed; continuing with deterministic results only.', [
                'repository' => $metadata->fullName,
                'exception' => $exception,
            ]);

            return AIReviewOutcome::unavailable();
        }
    }
}
