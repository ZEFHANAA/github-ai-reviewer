<?php

namespace App\Enums;

enum FindingStatus: string
{
    case Pass = 'pass';
    case Improvement = 'improvement';
    case Unknown = 'unknown';
}
