<?php

namespace App\Enums;

enum AnalysisStatus: string
{
    case Completed = 'completed';
    case Partial = 'partial';
}
