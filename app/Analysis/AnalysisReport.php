<?php

namespace App\Analysis;

final readonly class AnalysisReport
{
    /**
     * @param  array<string, int>  $categoryScores
     * @param  array{pass: int, improvement: int, unknown: int}  $summary
     * @param  array<int, AnalysisFinding>  $findings
     */
    public function __construct(
        public int $finalScore,
        public array $categoryScores,
        public array $summary,
        public array $findings,
    ) {}

    /**
     * @return array{final_score: int, category_scores: array<string, int>, summary: array{pass: int, improvement: int, unknown: int}, findings: array<int, AnalysisFinding>}
     */
    public function toArray(): array
    {
        return [
            'final_score' => $this->finalScore,
            'category_scores' => $this->categoryScores,
            'summary' => $this->summary,
            'findings' => $this->findings,
        ];
    }
}
