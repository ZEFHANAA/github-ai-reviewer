<?php

namespace App\AI;

use App\Analysis\AnalysisReport;
use App\ValueObjects\GitHubRepositoryMetadata;

final readonly class AIReviewRequest
{
    public function __construct(
        public GitHubRepositoryMetadata $metadata,
        public AnalysisReport $report,
        public string $prompt,
    ) {}
}
