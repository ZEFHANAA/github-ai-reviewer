<?php

namespace App\AI;

final readonly class AIReviewOutcome
{
    public const UNAVAILABLE_MESSAGE = 'AI review is temporarily unavailable.';

    private function __construct(
        public bool $isAvailable,
        public ?AIReviewResponse $response,
        public ?string $failureReason,
    ) {}

    public static function available(AIReviewResponse $response): self
    {
        return new self(true, $response, null);
    }

    public static function unavailable(): self
    {
        return new self(false, null, self::UNAVAILABLE_MESSAGE);
    }
}
