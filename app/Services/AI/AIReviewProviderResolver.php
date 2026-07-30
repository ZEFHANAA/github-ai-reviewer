<?php

namespace App\Services\AI;

use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\Providers\FakeAIReviewProvider;
use App\Services\AI\Providers\OpenAICompatibleReviewProvider;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves the configured AI review provider from config('services.ai').
 * Falls back to FakeAIReviewProvider when provider is 'fake' or unset.
 */
final class AIReviewProviderResolver
{
    public static function resolve(array $config): AIReviewProviderInterface
    {
        $provider = (string) ($config['provider'] ?? 'fake');
        $name = Str::lower($provider);

        return match ($name) {
            'fake' => new FakeAIReviewProvider,
            'openai_compatible', 'openai', 'openrouter' => self::openAICompatible($config),
            default => throw new RuntimeException("Unsupported AI provider [{$provider}]."),
        };
    }

    private static function openAICompatible(array $config): OpenAICompatibleReviewProvider
    {
        $baseUrl = $config['base_url'] ?? null;
        $key = $config['key'] ?? null;
        $model = $config['model'] ?? null;
        $endpoint = $config['endpoint'] ?? 'chat/completions';
        $timeout = (int) ($config['timeout'] ?? 30);

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new RuntimeException('The AI provider base_url is not configured.');
        }

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('The AI provider key is not configured.');
        }

        if (! is_string($model) || $model === '') {
            throw new RuntimeException('The AI provider model is not configured.');
        }

        return new OpenAICompatibleReviewProvider($baseUrl, $endpoint, $key, $model, $timeout);
    }
}
