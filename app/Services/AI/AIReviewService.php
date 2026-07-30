<?php

namespace App\Services\AI;

use App\AI\AIReviewResponse;
use App\Analysis\AnalysisReport;
use App\Contracts\AIReviewProviderInterface;
use App\ValueObjects\GitHubRepositoryMetadata;

final readonly class AIReviewService
{
    public function __construct(
        private AIReviewProviderInterface $provider,
        private AIReviewPromptBuilder $promptBuilder,
    ) {}

    /**
     * The report is read-only input; the AI layer never writes back to it.
     */
    public function review(GitHubRepositoryMetadata $metadata, AnalysisReport $report): AIReviewResponse
    {
        return $this->provider->review($this->promptBuilder->build($metadata, $report));
    }
}
