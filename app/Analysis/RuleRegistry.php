<?php

namespace App\Analysis;

use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\RuleCategory;
use App\Enums\RuleEligibility;

final class RuleRegistry
{
    /** @return array<string, RuleDefinition> */
    public static function all(): array
    {
        $i = FindingScope::Inspected;
        $g = FindingScope::Unavailable;

        $d = fn (
            string $id,
            RuleCategory $cat,
            RuleEligibility $elig,
            string $grp,
            FindingScope $scope,
            string $app,
            FindingSeverity $sev
        ): RuleDefinition => new RuleDefinition($id, $cat, $elig, $grp, $scope, $app, $sev);

        return [
            'DOC-README-001' => $d('DOC-README-001', RuleCategory::Documentation, RuleEligibility::Score, 'doc_files', $i, 'always', FindingSeverity::Medium),
            'DOC-LICENSE-001' => $d('DOC-LICENSE-001', RuleCategory::Documentation, RuleEligibility::Score, 'doc_files', $i, 'always', FindingSeverity::Medium),
            'DOC-CONTRIBUTING-001' => $d('DOC-CONTRIBUTING-001', RuleCategory::Documentation, RuleEligibility::Supporting, 'doc_files', $g, 'always', FindingSeverity::Low),
            'DOC-CONDUCT-001' => $d('DOC-CONDUCT-001', RuleCategory::Documentation, RuleEligibility::Supporting, 'doc_files', $g, 'always', FindingSeverity::Low),
            'DOC-CHANGELOG-001' => $d('DOC-CHANGELOG-001', RuleCategory::Documentation, RuleEligibility::Supporting, 'doc_files', $g, 'always', FindingSeverity::Low),
            'COMM-ISSUE-001' => $d('COMM-ISSUE-001', RuleCategory::Documentation, RuleEligibility::Supporting, 'issue_templates', $g, 'github', FindingSeverity::Low),
            'COMM-BUG-001' => $d('COMM-BUG-001', RuleCategory::Documentation, RuleEligibility::Informational, 'issue_templates', $g, 'github', FindingSeverity::Low),
            'COMM-FEATURE-001' => $d('COMM-FEATURE-001', RuleCategory::Documentation, RuleEligibility::Informational, 'issue_templates', $g, 'github', FindingSeverity::Low),
            'COMM-PR-001' => $d('COMM-PR-001', RuleCategory::Documentation, RuleEligibility::Informational, 'pr_template', $g, 'github', FindingSeverity::Low),
            'TEST-DIRECTORY-001' => $d('TEST-DIRECTORY-001', RuleCategory::Testing, RuleEligibility::Supporting, 'testing', $i, 'always', FindingSeverity::Medium),
            'TEST-CONFIG-001' => $d('TEST-CONFIG-001', RuleCategory::Testing, RuleEligibility::Supporting, 'testing', $i, 'always', FindingSeverity::Low),
            'SEC-ENV-001' => $d('SEC-ENV-001', RuleCategory::SecurityHygiene, RuleEligibility::Score, 'env_hygiene', $i, 'always', FindingSeverity::High),
            'SEC-POLICY-001' => $d('SEC-POLICY-001', RuleCategory::SecurityHygiene, RuleEligibility::Supporting, 'security_controls', $g, 'always', FindingSeverity::Medium),
            'SEC-DEPENDABOT-001' => $d('SEC-DEPENDABOT-001', RuleCategory::SecurityHygiene, RuleEligibility::Supporting, 'security_automation', $g, 'recognized_os', FindingSeverity::Low),
            'SEC-CODEQL-001' => $d('SEC-CODEQL-001', RuleCategory::SecurityHygiene, RuleEligibility::Supporting, 'security_automation', $g, 'github', FindingSeverity::Low),
            'STRUCT-MANIFEST-001' => $d('STRUCT-MANIFEST-001', RuleCategory::ProjectStructure, RuleEligibility::Score, 'project_structure', FindingScope::RootOnly, 'always', FindingSeverity::Medium),
            'STRUCT-SOURCE-001' => $d('STRUCT-SOURCE-001', RuleCategory::ProjectStructure, RuleEligibility::Supporting, 'project_structure', $i, 'always', FindingSeverity::Low),
            'GIT-CI-001' => $d('GIT-CI-001', RuleCategory::GitPractices, RuleEligibility::Supporting, 'ci', $g, 'github', FindingSeverity::Low),
            'GIT-IGNORE-001' => $d('GIT-IGNORE-001', RuleCategory::GitPractices, RuleEligibility::Score, 'env_hygiene', $i, 'always', FindingSeverity::Low),
            'CODE-CONFIG-001' => $d('CODE-CONFIG-001', RuleCategory::CodeQuality, RuleEligibility::Supporting, 'code_quality', $i, 'always', FindingSeverity::Low),
        ];
    }

    public static function get(string $id): ?RuleDefinition
    {
        return self::all()[$id] ?? null;
    }

    /** Severity for a rule; defaults to Low for unknown IDs. */
    public static function severity(string $id): FindingSeverity
    {
        return self::get($id)?->severity ?? FindingSeverity::Low;
    }

    /**
     * Group rules by category.
     *
     * @return array<string, array<int, RuleDefinition>>
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];
        foreach (self::all() as $rule) {
            $grouped[$rule->category->value][] = $rule;
        }

        return $grouped;
    }
}
