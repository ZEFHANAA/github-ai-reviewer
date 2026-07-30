<?php

namespace Tests\Unit\Analysis;

use App\Analysis\AnalysisFinding;
use App\Analysis\AnalysisReport;
use App\Analysis\FinalScoreCalculator;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use PHPUnit\Framework\TestCase;

class FinalScorePresentationTest extends TestCase
{
    private function finding(
        string $rule,
        RuleCategory $category,
        FindingStatus $status,
        FindingSeverity $severity = FindingSeverity::Medium,
        FindingScope $scope = FindingScope::Inspected,
    ): AnalysisFinding {
        return new AnalysisFinding(
            $rule,
            $category,
            $status,
            $scope,
            $severity,
            'title',
            'message'
        );
    }

    public function test_category_weights_sum_to_one_hundred(): void
    {
        $calculator = new FinalScoreCalculator;

        $this->assertSame(100, array_sum($calculator->categoryWeights()));
    }

    public function test_final_score_is_weighted_and_normalized_to_zero_hundred(): void
    {
        $calculator = new FinalScoreCalculator;
        $categoryScores = array_fill_keys(array_map(fn ($c) => $c->value, RuleCategory::cases()), 0);
        $categoryScores[RuleCategory::Documentation->value] = 80;
        $categoryScores[RuleCategory::SecurityHygiene->value] = 40;

        $this->assertSame(30, $calculator->finalScore($categoryScores));
        $this->assertSame(0, $calculator->finalScore([]));
        $this->assertSame(100, $calculator->finalScore(array_fill_keys(array_map(fn ($c) => $c->value, RuleCategory::cases()), 100)));
    }

    public function test_report_contains_consistent_scores_summary_and_findings(): void
    {
        $findings = [
            $this->finding('DOC-README-001', RuleCategory::Documentation, FindingStatus::Pass),
            $this->finding('SEC-ENV-001', RuleCategory::SecurityHygiene, FindingStatus::Improvement, FindingSeverity::High),
            $this->finding('STRUCT-MANIFEST-001', RuleCategory::ProjectStructure, FindingStatus::Unknown),
        ];

        $report = (new FinalScoreCalculator)->report($findings);

        $this->assertInstanceOf(AnalysisReport::class, $report);
        $this->assertSame(100, $report->categoryScores[RuleCategory::Documentation->value]);
        $this->assertSame(0, $report->categoryScores[RuleCategory::SecurityHygiene->value]);
        $this->assertSame(100, $report->categoryScores[RuleCategory::ProjectStructure->value]);
        $this->assertSame(40, $report->finalScore);
        $this->assertSame(1, $report->summary['pass']);
        $this->assertSame(1, $report->summary['improvement']);
        $this->assertSame(1, $report->summary['unknown']);
        $this->assertCount(3, $report->findings);
    }

    public function test_report_can_be_serialized_with_stable_presentation_shape(): void
    {
        $report = (new FinalScoreCalculator)->report([]);

        $this->assertSame([
            'final_score' => 0,
            'category_scores' => array_fill_keys(array_map(fn ($c) => $c->value, RuleCategory::cases()), 0),
            'summary' => ['pass' => 0, 'improvement' => 0, 'unknown' => 0],
            'findings' => [],
        ], $report->toArray());
    }
}

// Keep imports explicit: this test documents presentation only, not AnalysisFinding's contract.
