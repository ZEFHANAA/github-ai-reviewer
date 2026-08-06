<?php

namespace App\Services\AI\Providers;

use App\AI\AIReviewResponse;
use App\Services\AI\AIReviewResponseValidator;
use UnexpectedValueException;

/** Maps an OpenAI-compatible chat-completions payload to a validated review response. */
final readonly class OpenAICompatibleResponseMapper
{
    public function __construct(private AIReviewResponseValidator $validator) {}

    public function map(mixed $payload): AIReviewResponse
    {
        return $this->validator->validate($this->toResponse($this->decodeContent($payload)));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeContent(mixed $payload): array
    {
        $content = is_array($payload) ? ($payload['choices'][0]['message']['content'] ?? null) : null;

        if (! is_string($content) || trim($content) === '') {
            throw new UnexpectedValueException('The AI provider returned no message content.');
        }

        $json = $this->extractJson($content);

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UnexpectedValueException('The AI provider returned non-JSON content.');
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new UnexpectedValueException('The AI provider returned a non-object payload.');
        }

        return $decoded;
    }

    /** Pull the first balanced JSON object out of prose or reasoning noise. */
    private function extractJson(string $content): string
    {
        $start = strpos($content, '{');

        if ($start === false) {
            throw new UnexpectedValueException('The AI provider returned non-JSON content.');
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($content);

        for ($i = $start; $i < $len; $i++) {
            $ch = $content[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($ch === '\\' && $inString) {
                $escape = true;
                continue;
            }

            if ($ch === '"') {
                $inString = ! $inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
            }
        }

        throw new UnexpectedValueException('The AI provider returned non-JSON content.');
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function toResponse(array $decoded): AIReviewResponse
    {
        $summary = $decoded['repository_summary'] ?? null;

        if (! is_string($summary)) {
            throw new UnexpectedValueException('The AI provider returned no repository_summary.');
        }

        return new AIReviewResponse(
            $summary,
            $this->stringList($decoded, 'documentation_observations'),
            $this->stringList($decoded, 'maintainability_observations'),
            $this->stringList($decoded, 'potential_concerns'),
            $this->stringList($decoded, 'prioritized_recommendations'),
        );
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return list<string>
     */
    private function stringList(array $decoded, string $key): array
    {
        $value = $decoded[$key] ?? [];

        if (is_string($value)) {
            $value = [$value];
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw new UnexpectedValueException("The AI provider returned an invalid {$key} value.");
        }

        return array_values(array_map(
            fn (mixed $entry): string => is_string($entry)
                ? $entry
                : throw new UnexpectedValueException("The AI provider returned a non-string {$key} entry."),
            $value
        ));
    }
}
