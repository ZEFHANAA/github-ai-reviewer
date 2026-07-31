<?php

namespace Tests\Unit\Analysis;

use App\Analysis\FinalScoreCalculator;
use App\Enums\RuleCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FinalScoreCalculatorTest extends TestCase
{
    /**
     * @return array<string, int> every real category set to $default
     */
    private static function allCategories(int $default = 0): array
    {
        return array_fill_keys(
            array_map(fn (RuleCategory $c) => $c->value, RuleCategory::cases()),
            $default
        );
    }

    public function test_category_weights_match_the_documented_contract_exactly(): void
    {
        $this->assertSame([
            RuleCategory::Documentation->value => 25,
            RuleCategory::Testing->value => 15,
            RuleCategory::SecurityHygiene->value => 25,
            RuleCategory::ProjectStructure->value => 15,
            RuleCategory::GitPractices->value => 10,
            RuleCategory::CodeQuality->value => 10,
        ], (new FinalScoreCalculator)->categoryWeights());
    }

    /**
     * A single category at 100 with all others at 0 contributes exactly its
     * weight to the final score (denominator is the full 100 weight sum).
     *
     * @return array<string, array{RuleCategory, int}>
     */
    public static function perCategoryContributionProvider(): array
    {
        return [
            'Documentation weight 25' => [RuleCategory::Documentation, 25],
            'Testing weight 15' => [RuleCategory::Testing, 15],
            'Security hygiene weight 25' => [RuleCategory::SecurityHygiene, 25],
            'Project structure weight 15' => [RuleCategory::ProjectStructure, 15],
            'Git practices weight 10' => [RuleCategory::GitPractices, 10],
            'Code quality weight 10' => [RuleCategory::CodeQuality, 10],
        ];
    }

    #[DataProvider('perCategoryContributionProvider')]
    public function test_each_category_contributes_exactly_its_weight(RuleCategory $category, int $expectedContribution): void
    {
        $scores = self::allCategories(0);
        $scores[$category->value] = 100;

        $this->assertSame($expectedContribution, (new FinalScoreCalculator)->finalScore($scores));
    }

    public function test_unknown_category_does_not_affect_the_final_score(): void
    {
        $calculator = new FinalScoreCalculator;
        $withoutUnknown = self::allCategories(100);
        $withUnknownZero = $withoutUnknown + ['Nonexistent category' => 0];
        $withUnknownHundred = $withoutUnknown + ['Nonexistent category' => 100];

        $this->assertSame(100, $calculator->finalScore($withoutUnknown));
        $this->assertSame(100, $calculator->finalScore($withUnknownZero));
        $this->assertSame(100, $calculator->finalScore($withUnknownHundred));
    }

    public function test_unknown_category_cannot_inflate_an_all_zero_score(): void
    {
        $scores = self::allCategories(0) + ['Nonexistent category' => 100];

        $this->assertSame(0, (new FinalScoreCalculator)->finalScore($scores));
    }

    public function test_only_unknown_categories_produce_zero_without_crashing(): void
    {
        $scores = ['Nonexistent category' => 100, 'Another unknown' => 50];

        $this->assertSame(0, (new FinalScoreCalculator)->finalScore($scores));
    }

    public function test_a_partial_category_set_is_normalized_over_the_present_weights(): void
    {
        // (80 * 25 + 40 * 15) / (25 + 15) = 2600 / 40 = 65
        $scores = [
            RuleCategory::Documentation->value => 80,
            RuleCategory::Testing->value => 40,
        ];

        $this->assertSame(65, (new FinalScoreCalculator)->finalScore($scores));
    }

    /**
     * Rounding boundary with all six categories present (denominator 100):
     * final = round(sum(score * weight) / 100), PHP round() half away from zero.
     *
     * @return array<string, array{RuleCategory, int, int}>
     */
    public static function roundingBoundaryProvider(): array
    {
        return [
            'GitPractices 45 -> 4.5 rounds up to 5' => [RuleCategory::GitPractices, 45, 5],
            'GitPractices 44 -> 4.4 rounds down to 4' => [RuleCategory::GitPractices, 44, 4],
            'Documentation 50 -> 12.5 rounds up to 13' => [RuleCategory::Documentation, 50, 13],
            'Documentation 49 -> 12.25 rounds down to 12' => [RuleCategory::Documentation, 49, 12],
        ];
    }

    #[DataProvider('roundingBoundaryProvider')]
    public function test_rounding_boundary_is_half_away_from_zero(RuleCategory $category, int $categoryScore, int $expected): void
    {
        $scores = self::allCategories(0);
        $scores[$category->value] = $categoryScore;

        $this->assertSame($expected, (new FinalScoreCalculator)->finalScore($scores));
    }

    public function test_identical_input_produces_identical_final_score(): void
    {
        $calculator = new FinalScoreCalculator;
        $scores = self::allCategories(0);
        $scores[RuleCategory::Documentation->value] = 80;
        $scores[RuleCategory::SecurityHygiene->value] = 40;
        $scores[RuleCategory::GitPractices->value] = 45;

        $first = $calculator->finalScore($scores);
        $second = $calculator->finalScore($scores);

        $this->assertSame($first, $second);
        // (80 * 25 + 40 * 25 + 45 * 10) / 100 = 3450 / 100 = 34.5 -> 35
        $this->assertSame(35, $first);
    }
}
