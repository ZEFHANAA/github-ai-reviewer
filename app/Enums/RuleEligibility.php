<?php

namespace App\Enums;

enum RuleEligibility: string
{
    case Score = 'score';
    case Supporting = 'supporting';
    case Informational = 'informational';
}
