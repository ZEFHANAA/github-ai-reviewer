<?php

namespace App\Analysis;

use App\Enums\FindingScope;
use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\RuleCategory;

final readonly class AnalysisFinding
{
    public function __construct(
        public string $ruleIdentifier,
        public RuleCategory $category,
        public FindingStatus $status,
        public FindingScope $scope,
        public FindingSeverity $severity,
        public string $title,
        public string $message,
        public ?string $evidence = null,
        public ?string $recommendation = null
    ) {}
}
