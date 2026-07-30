<?php

namespace App\Analysis;

use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use App\Enums\RuleEligibility;

final class CategoryScorer
{
    /**
     * ponytail: eligibility-weighted base with all rules treated as satisfied;
     * real pass/fail detection arrives in Phase 5C/5D.
     *
     * @param  array<int, AnalysisFinding>  $findings
     */
    public function scoreCategory(RuleCategory $category, array $findings): int
    {
        $categoryFindings = array_filter($findings, fn ($f) => $f->category === $category);

        if (empty($categoryFindings)) {
            return 0;
        }

        $groups = $this->groupByCorrelation($categoryFindings);
        $totalWeight = 0;
        $scoredWeight = 0;

        foreach ($groups as $group) {
            $weight = $this->getWeight($group['eligibility']);
            $totalWeight += $weight;

            $penalty = $this->calculateGroupPenalty($group['findings']);
            if ($penalty < 1.0) {
                $scoredWeight += $weight * $penalty;
            } else {
                $scoredWeight += $weight;
            }
        }

        return (int) round(($scoredWeight / max($totalWeight, 1)) * 100);
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     * @return array<string, int>
     */
    public function scoreCategories(array $findings): array
    {
        $scores = [];
        foreach (RuleCategory::cases() as $category) {
            $scores[$category->value] = $this->scoreCategory($category, $findings);
        }

        return $scores;
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     * @return array<string, array{eligibility: RuleEligibility, findings: array<int, AnalysisFinding>}>
     */
    private function groupByCorrelation(array $findings): array
    {
        $groups = [];
        foreach ($findings as $finding) {
            $ruleDef = RuleRegistry::get($finding->ruleIdentifier);
            $groupKey = $ruleDef?->correlationGroup ?? $finding->ruleIdentifier;
            $eligibility = $ruleDef?->eligibility ?? RuleEligibility::Informational;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = ['eligibility' => $eligibility, 'findings' => []];
            }
            $groups[$groupKey]['findings'][] = $finding;
        }

        return $groups;
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     */
    private function calculateGroupPenalty(array $findings): float
    {
        $worstPenalty = 1.0;

        foreach ($findings as $finding) {
            $penalty = $this->findingPenalty($finding);
            if ($penalty < $worstPenalty) {
                $worstPenalty = $penalty;
            }
        }

        return $worstPenalty;
    }

    private function findingPenalty(AnalysisFinding $finding): float
    {
        if ($finding->status !== FindingStatus::Improvement) {
            return 1.0;
        }

        if (! in_array($finding->scope, [FindingScope::Inspected, FindingScope::RootOnly], true)) {
            return 1.0;
        }

        $ruleDef = RuleRegistry::get($finding->ruleIdentifier);
        $eligibility = $ruleDef?->eligibility ?? RuleEligibility::Informational;

        if ($eligibility !== RuleEligibility::Score) {
            return 1.0;
        }

        return match ($finding->severity) {
            FindingSeverity::High => 0.0,
            FindingSeverity::Medium => 0.5,
            FindingSeverity::Low => 0.75,
            FindingSeverity::Info => 1.0,
        };
    }

    private function getWeight(RuleEligibility $eligibility): int
    {
        return match ($eligibility) {
            RuleEligibility::Score => 3,
            RuleEligibility::Supporting => 2,
            RuleEligibility::Informational => 1,
        };
    }
}
