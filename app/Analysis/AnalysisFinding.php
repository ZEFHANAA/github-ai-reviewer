<?php

namespace App\Analysis;

final readonly class AnalysisFinding
{
    public function __construct(public string $ruleIdentifier, public string $category, public string $status, public string $title, public string $message, public ?string $evidence = null, public ?string $recommendation = null) {}
}
