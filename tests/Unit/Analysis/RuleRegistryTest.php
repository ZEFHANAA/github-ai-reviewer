<?php

namespace Tests\Unit\Analysis;

use App\Analysis\RuleDefinition;
use App\Analysis\RuleRegistry;
use App\Enums\FindingSeverity;
use App\Enums\RuleCategory;
use App\Enums\RuleEligibility;
use PHPUnit\Framework\TestCase;

class RuleRegistryTest extends TestCase
{
    public function test_rules_use_enums_for_category_and_eligibility(): void
    {
        $rules = RuleRegistry::all();

        $this->assertNotEmpty($rules);

        foreach ($rules as $rule) {
            $this->assertInstanceOf(RuleCategory::class, $rule->category);
            $this->assertInstanceOf(RuleEligibility::class, $rule->eligibility);
        }
    }

    public function test_every_rule_id_is_unique_and_matches_its_definition_id(): void
    {
        $rules = RuleRegistry::all();

        $ids = array_keys($rules);
        $this->assertSame($ids, array_unique($ids), 'Duplicate rule IDs detected in RuleRegistry::all().');

        foreach ($rules as $id => $rule) {
            $this->assertSame(
                $id,
                $rule->id,
                "Registry key [{$id}] does not match its RuleDefinition id [{$rule->id}]."
            );
        }
    }

    public function test_the_registry_contains_every_expected_rule_id(): void
    {
        $expectedIds = [
            'DOC-README-001',
            'DOC-LICENSE-001',
            'DOC-CONTRIBUTING-001',
            'DOC-CONDUCT-001',
            'DOC-CHANGELOG-001',
            'COMM-ISSUE-001',
            'COMM-BUG-001',
            'COMM-FEATURE-001',
            'COMM-PR-001',
            'TEST-DIRECTORY-001',
            'TEST-CONFIG-001',
            'SEC-ENV-001',
            'SEC-POLICY-001',
            'SEC-DEPENDABOT-001',
            'SEC-CODEQL-001',
            'STRUCT-MANIFEST-001',
            'STRUCT-SOURCE-001',
            'GIT-CI-001',
            'GIT-IGNORE-001',
            'CODE-CONFIG-001',
        ];

        $this->assertSame($expectedIds, array_keys(RuleRegistry::all()), 'Rule ID set or order changed.');
    }

    public function test_every_rule_has_a_valid_category_and_every_category_is_represented(): void
    {
        $rules = RuleRegistry::all();

        foreach ($rules as $id => $rule) {
            $this->assertContains(
                $rule->category,
                RuleCategory::cases(),
                "Rule [{$id}] has an invalid category [{$rule->category->value}]."
            );
        }

        $usedCategoryValues = array_unique(array_map(fn (RuleDefinition $r) => $r->category->value, $rules));
        sort($usedCategoryValues);
        $expectedCategoryValues = array_map(fn (RuleCategory $c) => $c->value, RuleCategory::cases());
        sort($expectedCategoryValues);
        $this->assertSame(
            $expectedCategoryValues,
            $usedCategoryValues,
            'Every RuleCategory enum case must be represented by at least one rule.'
        );
    }

    public function test_severity_returns_the_defined_value_for_known_rules_and_low_for_unknown(): void
    {
        $this->assertSame(
            RuleRegistry::get('SEC-ENV-001')->severity,
            RuleRegistry::severity('SEC-ENV-001'),
            'severity() must return the rule definition severity for a known ID.'
        );

        $this->assertSame(
            RuleRegistry::get('DOC-README-001')->severity,
            RuleRegistry::severity('DOC-README-001'),
            'severity() must return the rule definition severity for a known ID.'
        );

        $this->assertSame(
            FindingSeverity::Low,
            RuleRegistry::severity('DOES-NOT-EXIST-999'),
            'severity() must default to Low for an unknown rule ID.'
        );
    }

    public function test_grouped_by_category_is_consistent_with_all_rules_and_covers_every_category(): void
    {
        $all = RuleRegistry::all();
        $grouped = RuleRegistry::groupedByCategory();

        $this->assertSame(
            count(RuleCategory::cases()),
            count($grouped),
            'groupedByCategory() must produce one group per RuleCategory case.'
        );

        $groupedCount = 0;
        foreach ($grouped as $categoryValue => $groupRules) {
            $this->assertContains(
                RuleCategory::from($categoryValue),
                RuleCategory::cases(),
                "groupedByCategory() returned unknown category [{$categoryValue}]."
            );
            foreach ($groupRules as $rule) {
                $this->assertInstanceOf(RuleDefinition::class, $rule);
                $this->assertSame($rule->category->value, $categoryValue, 'Rule placed under wrong category group.');
                $this->assertArrayHasKey($rule->id, $all, 'groupedByCategory() surfaced a rule not in all().');
            }
            $groupedCount += count($groupRules);
        }

        $this->assertSame(count($all), $groupedCount, 'Total grouped rules must equal total registered rules.');
    }
}
