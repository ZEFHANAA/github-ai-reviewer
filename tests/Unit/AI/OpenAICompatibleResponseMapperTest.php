<?php

namespace Tests\Unit\AI;

use App\AI\AIReviewResponse;
use App\Services\AI\AIReviewResponseValidator;
use App\Services\AI\Exceptions\AIReviewValidationException;
use App\Services\AI\Providers\OpenAICompatibleResponseMapper;
use JsonException;
use Tests\TestCase;
use UnexpectedValueException;

class OpenAICompatibleResponseMapperTest extends TestCase
{
    public function test_it_maps_a_chat_completions_payload_to_a_response(): void
    {
        $response = $this->mapper()->map($this->payload($this->content()));

        $this->assertInstanceOf(AIReviewResponse::class, $response);
        $this->assertSame($this->content(), $response->toArray());
    }

    public function test_it_normalizes_through_the_validator(): void
    {
        $content = $this->content();
        $content['repository_summary'] = '  Repository summary.  ';
        $content['potential_concerns'] = ['Potential concern.', '   ', ''];

        $response = $this->mapper()->map($this->payload($content));

        $this->assertSame('Repository summary.', $response->repositorySummary);
        $this->assertSame(['Potential concern.'], $response->potentialConcerns);
    }

    public function test_it_rejects_a_missing_message_content(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map(['choices' => []]);
    }

    public function test_it_rejects_a_blank_message_content(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map(['choices' => [['message' => ['content' => '   ']]]]);
    }

    public function test_it_rejects_a_non_array_payload(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map('not a payload');
    }

    public function test_it_rejects_malformed_json_content(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map(['choices' => [['message' => ['content' => 'not json']]]]);
    }

    public function test_it_rejects_a_non_object_json_content(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map(['choices' => [['message' => ['content' => '"a string"']]]]);
    }

    public function test_it_rejects_a_missing_repository_summary(): void
    {
        $content = $this->content();
        unset($content['repository_summary']);

        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map($this->payload($content));
    }

    public function test_it_rejects_a_non_list_section(): void
    {
        $content = $this->content();
        $content['documentation_observations'] = ['first' => 'Documentation observation.'];

        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map($this->payload($content));
    }

    public function test_it_rejects_a_non_string_section_entry(): void
    {
        $content = $this->content();
        $content['maintainability_observations'] = [42];

        $this->expectException(UnexpectedValueException::class);

        $this->mapper()->map($this->payload($content));
    }

    public function test_it_rejects_an_empty_summary_via_the_validator(): void
    {
        $content = $this->content();
        $content['repository_summary'] = '   ';

        $this->expectException(AIReviewValidationException::class);

        $this->mapper()->map($this->payload($content));
    }

    private function mapper(): OpenAICompatibleResponseMapper
    {
        return new OpenAICompatibleResponseMapper(new AIReviewResponseValidator);
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function payload(array $content): array
    {
        return [
            'choices' => [[
                'message' => ['content' => json_encode($content, JSON_THROW_ON_ERROR)],
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function content(): array
    {
        return [
            'repository_summary' => 'Repository summary.',
            'documentation_observations' => ['Documentation observation.'],
            'maintainability_observations' => ['Maintainability observation.'],
            'potential_concerns' => ['Potential concern.'],
            'prioritized_recommendations' => ['Prioritized recommendation.'],
        ];
    }
}
