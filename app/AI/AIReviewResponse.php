<?php

namespace App\AI;

final readonly class AIReviewResponse
{
    /**
     * @param  list<string>  $documentationObservations
     * @param  list<string>  $maintainabilityObservations
     * @param  list<string>  $potentialConcerns
     * @param  list<string>  $prioritizedRecommendations
     */
    public function __construct(
        public string $repositorySummary,
        public array $documentationObservations,
        public array $maintainabilityObservations,
        public array $potentialConcerns,
        public array $prioritizedRecommendations,
    ) {}

    /**
     * @return array{repository_summary: string, documentation_observations: list<string>, maintainability_observations: list<string>, potential_concerns: list<string>, prioritized_recommendations: list<string>}
     */
    public function toArray(): array
    {
        return [
            'repository_summary' => $this->repositorySummary,
            'documentation_observations' => $this->documentationObservations,
            'maintainability_observations' => $this->maintainabilityObservations,
            'potential_concerns' => $this->potentialConcerns,
            'prioritized_recommendations' => $this->prioritizedRecommendations,
        ];
    }
}
