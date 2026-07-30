<?php

namespace App\Enums;

enum FindingScope: string
{
    case Inspected = 'inspected';
    case Unavailable = 'unavailable';
    case OmittedBudget = 'omitted_budget';
    case RootOnly = 'root_only';
    case NotApplicable = 'not_applicable';
}
