<?php

namespace App\AI;

/**
 * Immutable presentation DTO carrying everything the report view needs to
 * render AI enrichment. Views read sourceLabel/notice/sections only — no
 * provider names, availability branching, or business rules live in Blade.
 */
final readonly class AIReviewOutcome
{
    public const UNAVAILABLE_MESSAGE = 'AI review is temporarily unavailable.';

    public const AVAILABLE_SOURCE = 'AI-assisted qualitative review';

    public const UNAVAILABLE_SOURCE = 'AI enrichment unavailable';

    public const SECTION_TITLES = [
        'Repository Summary',
        'Documentation Observations',
        'Maintainability Observations',
        'Potential Concerns',
        'Prioritized Recommendations',
    ];

    private function __construct(
        public bool $isAvailable,
        public ?AIReviewResponse $response,
        public ?string $failureReason,
        public string $sourceLabel,
        public ?string $notice,
        public array $sections,
    ) {}

    public static function available(AIReviewResponse $response): self
    {
        return new self(
            isAvailable: true,
            response: $response,
            failureReason: null,
            sourceLabel: self::AVAILABLE_SOURCE,
            notice: null,
            sections: self::sections($response),
        );
    }

    public static function unavailable(): self
    {
        return new self(
            isAvailable: false,
            response: null,
            failureReason: self::UNAVAILABLE_MESSAGE,
            sourceLabel: self::UNAVAILABLE_SOURCE,
            notice: self::UNAVAILABLE_MESSAGE,
            sections: [],
        );
    }

    /**
     * Project the response into a view-friendly list of titled sections so
     * Blade can render a flat list without mapping domain field names.
     *
     * @return list<array{title: string, entries: list<string>}>
     */
    private static function sections(AIReviewResponse $response): array
    {
        return [
            ['title' => self::SECTION_TITLES[0], 'entries' => [$response->repositorySummary]],
            ['title' => self::SECTION_TITLES[1], 'entries' => $response->documentationObservations],
            ['title' => self::SECTION_TITLES[2], 'entries' => $response->maintainabilityObservations],
            ['title' => self::SECTION_TITLES[3], 'entries' => $response->potentialConcerns],
            ['title' => self::SECTION_TITLES[4], 'entries' => $response->prioritizedRecommendations],
        ];
    }
}
