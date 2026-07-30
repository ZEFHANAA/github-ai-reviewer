<?php

namespace App\Services\AI;

use App\AI\AIReviewResponse;
use App\Services\AI\Exceptions\AIReviewValidationException;

final class AIReviewResponseValidator
{
    public const MAX_SUMMARY_LENGTH = 2000;

    public const MAX_ENTRY_LENGTH = 1000;

    public const MAX_LIST_ENTRIES = 20;

    public function validate(AIReviewResponse $response): AIReviewResponse
    {
        $summary = trim((string) $response->repositorySummary);

        if ($summary === '') {
            $this->fail('repository_summary must not be empty.');
        }

        if (mb_strlen($summary) > self::MAX_SUMMARY_LENGTH) {
            $this->fail('repository_summary exceeds the maximum length.');
        }

        return new AIReviewResponse(
            $summary,
            $this->cleanList($response->documentationObservations, 'documentation_observations'),
            $this->cleanList($response->maintainabilityObservations, 'maintainability_observations'),
            $this->cleanList($response->potentialConcerns, 'potential_concerns'),
            $this->cleanList($response->prioritizedRecommendations, 'prioritized_recommendations'),
        );
    }

    /** @return list<string> */
    private function cleanList(array $entries, string $field): array
    {
        if (count($entries) > self::MAX_LIST_ENTRIES) {
            $this->fail($field.' exceeds the maximum entry count.');
        }

        $cleaned = [];

        foreach ($entries as $entry) {
            if (! is_string($entry)) {
                $this->fail($field.' must contain only strings.');
            }

            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            if (mb_strlen($entry) > self::MAX_ENTRY_LENGTH) {
                $this->fail($field.' contains an entry exceeding the maximum length.');
            }

            $cleaned[] = $entry;
        }

        return $cleaned;
    }

    private function fail(string $reason): never
    {
        throw new AIReviewValidationException($reason);
    }
}
