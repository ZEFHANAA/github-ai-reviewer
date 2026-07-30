<?php

namespace App\Analysis;

use App\Enums\RuleCategory;

final class FinalScoreCalculator
{
    private CategoryScorer $categoryScorer;

    public function __construct()
    {
        $this->categoryScorer = new CategoryScorer;
    }

    public function categoryWeights(): array
    {
        return [
            RuleCategory::Documentation->value => 25,
            RuleCategory::Testing->value => 15,
            RuleCategory::SecurityHygiene->value => 25,
            RuleCategory::ProjectStructure->value => 15,
            RuleCategory::GitPractices->value => 10,
            RuleCategory::CodeQuality->value => 10,
        ];
    }

    public function finalScore(array $categoryScores): int
    {
        $weights = $this->categoryWeights();
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($categoryScores as $category => $score) {
            $weight = $weights[$category] ?? 0;
            $totalWeightedScore += $score * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0) {
            return 0;
        }

        return (int) round($totalWeightedScore / $totalWeight);
    }

    public function report(array $findings): AnalysisReport
    {
        $categoryScores = $this->categoryScorer->scoreCategories($findings);
        $finalScore = $this->finalScore($categoryScores);
        $summary = $this->summarizeFindings($findings);

        return new AnalysisReport(
            $finalScore,
            $categoryScores,
            $summary,
            $findings
        );
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     * @return array{pass: int, improvement: int, unknown: int}
     */
    private function summarizeFindings(array $findings): array
    {
        $summary = ['pass' => 0, 'improvement' => 0, 'unknown' => 0];

        foreach ($findings as $finding) {
            match ($finding->status->value) {
                'pass' => $summary['pass']++,
                'improvement' => $summary['improvement']++,
                'unknown' => $summary['unknown']++,
                default => null,
            };
        }

        return $summary;
    }
}
