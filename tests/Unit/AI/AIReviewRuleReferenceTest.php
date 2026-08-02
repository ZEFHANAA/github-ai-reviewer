<?php

namespace Tests\Unit\AI;

use App\AI\AIReviewResponse;
use App\Services\AI\AIReviewResponseValidator;
use App\Services\AI\Exceptions\AIReviewValidationException;
use Tests\TestCase;

class AIReviewRuleReferenceTest extends TestCase
{
    public function test_it_accepts_only_known_deterministic_rule_references(): void
    {
        $response = (new AIReviewResponseValidator)->validateReferences($this->response(), ['DOC-README-001']);

        $this->assertSame($this->response()->toArray(), $response->toArray());
    }

    public function test_it_rejects_an_unknown_rule_reference(): void
    {
        $response = new AIReviewResponse(
            '[DOC-README-001] Summary.',
            ['[DOC-LICENSE-999] Invented concern.'],
            [],
            [],
            [],
        );

        $this->expectException(AIReviewValidationException::class);
        $this->expectExceptionMessage('unknown deterministic rule ID');

        (new AIReviewResponseValidator)->validateReferences($response, ['DOC-README-001']);
    }

    public function test_it_rejects_ai_list_entries_without_a_rule_reference(): void
    {
        $response = new AIReviewResponse(
            '[DOC-README-001] Summary.',
            ['Documentation concern without a rule ID.'],
            [],
            [],
            [],
        );

        $this->expectException(AIReviewValidationException::class);
        $this->expectExceptionMessage('must begin with a deterministic rule ID');

        (new AIReviewResponseValidator)->validateReferences($response, ['DOC-README-001']);
    }

    public function test_it_rejects_a_summary_without_a_rule_reference(): void
    {
        $response = new AIReviewResponse('Summary without a rule ID.', [], [], [], []);

        $this->expectException(AIReviewValidationException::class);
        $this->expectExceptionMessage('repository_summary must begin with a deterministic rule ID');

        (new AIReviewResponseValidator)->validateReferences($response, ['DOC-README-001']);
    }

    private function response(): AIReviewResponse
    {
        return new AIReviewResponse(
            '[DOC-README-001] Summary.',
            ['[DOC-README-001] Documentation concern.'],
            [],
            [],
            ['[DOC-README-001] Add a clearer README.'],
        );
    }
}
