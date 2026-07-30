<?php

namespace App\Enums;

enum RuleCategory: string
{
    case Documentation = 'Documentation';
    case Testing = 'Testing';
    case SecurityHygiene = 'Security hygiene';
    case ProjectStructure = 'Project structure';
    case GitPractices = 'Git practices';
    case CodeQuality = 'Code quality';
}
