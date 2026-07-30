<?php

namespace Tests\Unit\Analysis;

use App\Analysis\AnalysisFinding;
use App\Analysis\CategoryScorer;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use PHPUnit\Framework\TestCase;

class CategoryScoringTest extends TestCase
{
    private function finding(
        string $rule,
        RuleCategory $category,
        FindingStatus $status,
        FindingScope $scope = FindingScope::Inspected,
        FindingSeverity $severity = FindingSeverity::Medium,
    ): AnalysisFinding {
        return new AnalysisFinding($rule, $category, $status, $scope, $severity, 'title', 'message');
    }

    public function test_score_category_uses_finding_status_and_severity(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('DOC-README-001', RuleCategory::Documentation, FindingStatus::Pass),
            $this->finding('DOC-LICENSE-001', RuleCategory::Documentation, FindingStatus::Improvement, severity: FindingSeverity::Medium),
        ];

        $this->assertSame(50, $scorer->scoreCategory(RuleCategory::Documentation, $findings));
    }

    public function test_unknown_and_non_inspected_findings_do_not_reduce_score(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('DOC-README-001', RuleCategory::Documentation, FindingStatus::Unknown),
            $this->finding('DOC-LICENSE-001', RuleCategory::Documentation, FindingStatus::Improvement, FindingScope::Unavailable),
            $this->finding('DOC-CONTRIBUTING-001', RuleCategory::Documentation, FindingStatus::Improvement, FindingScope::OmittedBudget),
        ];

        $this->assertSame(100, $scorer->scoreCategory(RuleCategory::Documentation, $findings));
    }

    public function test_only_score_eligible_rules_reduce_category_score(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('DOC-CONTRIBUTING-001', RuleCategory::Documentation, FindingStatus::Improvement),
            $this->finding('COMM-BUG-001', RuleCategory::Documentation, FindingStatus::Improvement),
        ];

        $this->assertSame(100, $scorer->scoreCategory(RuleCategory::Documentation, $findings));
    }

    public function test_correlation_group_counts_worst_score_eligible_finding_once(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('DOC-README-001', RuleCategory::Documentation, FindingStatus::Improvement, severity: FindingSeverity::Low),
            $this->finding('DOC-LICENSE-001', RuleCategory::Documentation, FindingStatus::Improvement, severity: FindingSeverity::High),
        ];

        $this->assertSame(0, $scorer->scoreCategory(RuleCategory::Documentation, $findings));
    }

    public function test_categories_are_aggregated_without_final_category_weights(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('DOC-README-001', RuleCategory::Documentation, FindingStatus::Pass),
            $this->finding('SEC-ENV-001', RuleCategory::SecurityHygiene, FindingStatus::Improvement, severity: FindingSeverity::High),
        ];

        $scores = $scorer->scoreCategories($findings);

        $this->assertSame(100, $scores[RuleCategory::Documentation->value]);
        $this->assertSame(0, $scores[RuleCategory::SecurityHygiene->value]);
        $this->assertCount(count(RuleCategory::cases()), $scores);
    }

    public function test_empty_category_has_no_score(): void
    {
        $this->assertSame(0, (new CategoryScorer)->scoreCategory(RuleCategory::CodeQuality, []));
    }
}
