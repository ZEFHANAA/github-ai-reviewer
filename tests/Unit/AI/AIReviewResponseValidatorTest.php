<?php

namespace Tests\Unit\AI;

use App\AI\AIReviewResponse;
use App\Services\AI\AIReviewResponseValidator;
use App\Services\AI\Exceptions\AIReviewValidationException;
use PHPUnit\Framework\TestCase;

class AIReviewResponseValidatorTest extends TestCase
{
    public function test_a_valid_response_passes_through_unchanged(): void
    {
        $response = $this->response();

        $validated = (new AIReviewResponseValidator)->validate($response);

        $this->assertSame($response->toArray(), $validated->toArray());
    }

    public function test_it_rejects_an_empty_summary(): void
    {
        $this->expectException(AIReviewValidationException::class);

        (new AIReviewResponseValidator)->validate($this->response(summary: '   '));
    }

    public function test_it_rejects_a_summary_exceeding_the_length_limit(): void
    {
        $this->expectException(AIReviewValidationException::class);

        (new AIReviewResponseValidator)->validate(
            $this->response(summary: str_repeat('a', AIReviewResponseValidator::MAX_SUMMARY_LENGTH + 1))
        );
    }

    public function test_it_rejects_non_string_list_entries(): void
    {
        $this->expectException(AIReviewValidationException::class);

        (new AIReviewResponseValidator)->validate($this->response(documentation: ['ok', 42]));
    }

    public function test_it_drops_blank_list_entries(): void
    {
        $validated = (new AIReviewResponseValidator)->validate(
            $this->response(documentation: ['first', '   ', '', 'second'])
        );

        $this->assertSame(['first', 'second'], $validated->documentationObservations);
    }

    public function test_it_rejects_a_list_exceeding_the_entry_limit(): void
    {
        $this->expectException(AIReviewValidationException::class);

        (new AIReviewResponseValidator)->validate(
            $this->response(documentation: array_fill(0, AIReviewResponseValidator::MAX_LIST_ENTRIES + 1, 'entry'))
        );
    }

    public function test_it_rejects_a_list_entry_exceeding_the_length_limit(): void
    {
        $this->expectException(AIReviewValidationException::class);

        (new AIReviewResponseValidator)->validate(
            $this->response(documentation: [str_repeat('a', AIReviewResponseValidator::MAX_ENTRY_LENGTH + 1)])
        );
    }

    public function test_a_list_may_become_empty_after_dropping_blank_entries(): void
    {
        $validated = (new AIReviewResponseValidator)->validate($this->response(documentation: ['   ']));

        $this->assertSame([], $validated->documentationObservations);
    }

    /**
     * @param  array<int, mixed>  $documentation
     */
    private function response(string $summary = 'A useful summary.', array $documentation = ['Docs look fine.']): AIReviewResponse
    {
        /** @phpstan-ignore-next-line intentionally allows invalid entries for negative tests */
        return new AIReviewResponse(
            $summary,
            $documentation,
            ['Maintained regularly.'],
            ['No major concerns.'],
            ['Add a changelog.']
        );
    }
}
