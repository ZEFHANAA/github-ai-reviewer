<?php

namespace App\Contracts;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;

interface AIReviewProviderInterface
{
    /**
     * Produce a qualitative review for an already-scored repository.
     *
     * Implementations must never alter deterministic findings or scores.
     */
    public function review(AIReviewRequest $request): AIReviewResponse;
}
