<?php

namespace App\Services\AI\Providers;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Contracts\AIReviewProviderInterface;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

/**
 * OpenAI-compatible chat-completions provider. All HTTP lives here; endpoint,
 * model, key, and timeout come from config('services.ai'). Failures throw and
 * are absorbed by SafeAIReviewService, so deterministic analysis is unaffected.
 */
final readonly class OpenAICompatibleReviewProvider implements AIReviewProviderInterface
{
    public function __construct(
        private string $baseUrl,
        private string $endpoint,
        private string $key,
        private string $model,
        private int $timeout,
    ) {}

    public function review(AIReviewRequest $request): AIReviewResponse
    {
        $payload = Http::withToken($this->key)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->post(rtrim($this->baseUrl, '/').'/'.ltrim($this->endpoint, '/'), [
                'model' => $this->model,
                'messages' => [['role' => 'user', 'content' => $request->prompt]],
                'response_format' => ['type' => 'json_object'],
            ])
            ->throw()
            ->json();

        return $this->toResponse($this->decodeContent($payload));
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

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new UnexpectedValueException('The AI provider returned a non-object payload.');
        }

        return $decoded;
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
