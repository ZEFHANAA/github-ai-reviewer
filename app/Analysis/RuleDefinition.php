<?php

namespace App\Analysis;

use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\RuleCategory;
use App\Enums\RuleEligibility;

final readonly class RuleDefinition
{
    public function __construct(
        public string $id,
        public RuleCategory $category,
        public RuleEligibility $eligibility,
        public string $correlationGroup,
        public FindingScope $scopeRequirement,
        public string $applicability,
        public FindingSeverity $severity,
        public int $version = 1,
    ) {}
}
