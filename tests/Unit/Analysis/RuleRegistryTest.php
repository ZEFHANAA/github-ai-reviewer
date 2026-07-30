<?php

namespace Tests\Unit\Analysis;

use App\Analysis\RuleRegistry;
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
}
