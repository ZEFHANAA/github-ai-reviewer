<?php

namespace App\Services\AI\Providers;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Contracts\AIReviewProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI-compatible chat-completions provider. This class owns HTTP transport
 * only; endpoint, model, key, and timeout come from config('services.ai') and
 * response parsing belongs to OpenAICompatibleResponseMapper. Failures throw
 * and are absorbed by SafeAIReviewService, so deterministic analysis is safe.
 */
final readonly class OpenAICompatibleReviewProvider implements AIReviewProviderInterface
{
    public function __construct(
        private OpenAICompatibleResponseMapper $mapper,
        private string $baseUrl,
        private string $endpoint,
        private string $key,
        private string $model,
        private int $timeout,
    ) {}

    public function review(AIReviewRequest $request): AIReviewResponse
    {
        $response = Http::withToken($this->key)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->post(rtrim($this->baseUrl, '/').'/'.ltrim($this->endpoint, '/'), [
                'model' => $this->model,
                'messages' => [['role' => 'user', 'content' => $request->prompt]],
                'response_format' => ['type' => 'json_object'],
            ])
            ->throw();

        return $this->mapper->map($response->json());
    }
}
