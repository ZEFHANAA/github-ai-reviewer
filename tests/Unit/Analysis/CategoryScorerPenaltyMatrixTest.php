<?php

namespace Tests\Unit\Analysis;

use App\Analysis\AnalysisFinding;
use App\Analysis\CategoryScorer;
use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CategoryScorerPenaltyMatrixTest extends TestCase
{
    /**
     * Build a finding from a real registry rule, overriding only what the
     * penalty matrix reacts to. The rule identifier drives eligibility via
     * RuleRegistry, so a Score-eligible rule is used by default.
     */
    private function finding(
        string $rule,
        RuleCategory $category,
        FindingStatus $status,
        FindingScope $scope = FindingScope::Inspected,
        FindingSeverity $severity = FindingSeverity::High,
    ): AnalysisFinding {
        return new AnalysisFinding($rule, $category, $status, $scope, $severity, 'title', 'message');
    }

    /**
     * Penalty matrix for a single Score-eligible Improvement finding.
     *
     * Formula: score = round((weight * penalty / weight) * 100) = round(penalty * 100).
     * High   -> penalty 0.0  -> 0
     * Medium -> penalty 0.5  -> 50
     * Low    -> penalty 0.75 -> 75
     * Info   -> penalty 1.0  -> 100  (reward/no-reduction branch for Improvement)
     *
     * @return array<string, array{FindingSeverity, int}>
     */
    public static function severityPenaltyProvider(): array
    {
        return [
            'High severity -> 0' => [FindingSeverity::High, 0],
            'Medium severity -> 50' => [FindingSeverity::Medium, 50],
            'Low severity -> 75' => [FindingSeverity::Low, 75],
            'Info severity -> 100 (reward branch)' => [FindingSeverity::Info, 100],
        ];
    }

    #[DataProvider('severityPenaltyProvider')]
    public function test_severity_penalty_matrix_on_single_score_eligible_finding(FindingSeverity $severity, int $expected): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('SEC-ENV-001', RuleCategory::SecurityHygiene, FindingStatus::Improvement, FindingScope::Inspected, $severity),
        ];

        $this->assertSame($expected, $scorer->scoreCategory(RuleCategory::SecurityHygiene, $findings));
    }

    public function test_pass_status_does_not_reduce_score(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('SEC-ENV-001', RuleCategory::SecurityHygiene, FindingStatus::Pass, FindingScope::Inspected, FindingSeverity::High),
        ];

        $this->assertSame(100, $scorer->scoreCategory(RuleCategory::SecurityHygiene, $findings));
    }

    public function test_unknown_status_does_not_reduce_score(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('SEC-ENV-001', RuleCategory::SecurityHygiene, FindingStatus::Unknown, FindingScope::Inspected, FindingSeverity::High),
        ];

        $this->assertSame(100, $scorer->scoreCategory(RuleCategory::SecurityHygiene, $findings));
    }

    /**
     * Scope gate: only Inspected and RootOnly are penalized.
     *
     * @return array<string, array{FindingScope}>
     */
    public static function nonPenalizedScopeProvider(): array
    {
        return [
            'Unavailable scope -> no penalty' => [FindingScope::Unavailable],
            'OmittedBudget scope -> no penalty' => [FindingScope::OmittedBudget],
            'NotApplicable scope -> no penalty' => [FindingScope::NotApplicable],
        ];
    }

    #[DataProvider('nonPenalizedScopeProvider')]
    public function test_non_inspected_scopes_do_not_reduce_score(FindingScope $scope): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('SEC-ENV-001', RuleCategory::SecurityHygiene, FindingStatus::Improvement, $scope, FindingSeverity::High),
        ];

        $this->assertSame(100, $scorer->scoreCategory(RuleCategory::SecurityHygiene, $findings));
    }

    public function test_root_only_scope_applies_severity_penalty(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('STRUCT-MANIFEST-001', RuleCategory::ProjectStructure, FindingStatus::Improvement, FindingScope::RootOnly, FindingSeverity::Medium),
        ];

        $this->assertSame(50, $scorer->scoreCategory(RuleCategory::ProjectStructure, $findings));
    }

    /**
     * Eligibility gate: only Score-eligible rules reduce score.
     *
     * @return array<string, array{string, RuleCategory}>
     */
    public static function nonScoreEligibleProvider(): array
    {
        return [
            'Supporting eligibility -> no penalty' => ['TEST-DIRECTORY-001', RuleCategory::Testing],
            'Informational eligibility -> no penalty' => ['COMM-BUG-001', RuleCategory::Documentation],
        ];
    }

    #[DataProvider('nonScoreEligibleProvider')]
    public function test_non_score_eligible_rules_do_not_reduce_score(string $rule, RuleCategory $category): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding($rule, $category, FindingStatus::Improvement, FindingScope::Inspected, FindingSeverity::High),
        ];

        $this->assertSame(100, $scorer->scoreCategory($category, $findings));
    }

    public function test_identical_input_produces_identical_score(): void
    {
        $scorer = new CategoryScorer;
        $findings = [
            $this->finding('SEC-ENV-001', RuleCategory::SecurityHygiene, FindingStatus::Improvement, FindingScope::Inspected, FindingSeverity::Medium),
            $this->finding('SEC-POLICY-001', RuleCategory::SecurityHygiene, FindingStatus::Pass, FindingScope::Unavailable, FindingSeverity::Medium),
        ];

        $first = $scorer->scoreCategory(RuleCategory::SecurityHygiene, $findings);
        $second = $scorer->scoreCategory(RuleCategory::SecurityHygiene, $findings);

        $this->assertSame($first, $second);
        $this->assertSame(70, $first);
    }
}
