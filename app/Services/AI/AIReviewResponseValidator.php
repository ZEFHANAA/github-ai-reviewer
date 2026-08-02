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

    /**
     * Accept AI prose only when every rendered entry starts with a rule ID from
     * the authoritative deterministic report. This prevents invented findings
     * while preserving AI's role: explaining already-known deterministic data.
     *
     * @param  list<string>  $allowedRuleIds
     */
    public function validateReferences(AIReviewResponse $response, array $allowedRuleIds): AIReviewResponse
    {
        $allowed = array_fill_keys($allowedRuleIds, true);
        $this->validateReference($response->repositorySummary, 'repository_summary', $allowed);

        foreach ([
            'documentation_observations' => $response->documentationObservations,
            'maintainability_observations' => $response->maintainabilityObservations,
            'potential_concerns' => $response->potentialConcerns,
            'prioritized_recommendations' => $response->prioritizedRecommendations,
        ] as $field => $entries) {
            foreach ($entries as $entry) {
                $this->validateReference($entry, $field, $allowed);
            }
        }

        return $response;
    }

    /** @param array<string, true> $allowed */
    private function validateReference(string $entry, string $field, array $allowed): void
    {
        if (! preg_match('/^\[([A-Z]+-[A-Z]+-\d+)\]\s+/', $entry, $matches)) {
            $this->fail("{$field} must begin with a deterministic rule ID in brackets.");
        }

        if (! isset($allowed[$matches[1]])) {
            $this->fail("{$field} references an unknown deterministic rule ID.");
        }
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
