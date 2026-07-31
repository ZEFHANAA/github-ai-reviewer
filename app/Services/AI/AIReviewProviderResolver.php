<?php

namespace App\Services\AI;

use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\Providers\FakeAIReviewProvider;
use App\Services\AI\Providers\OpenAICompatibleResponseMapper;
use App\Services\AI\Providers\OpenAICompatibleReviewProvider;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves the configured AI review provider from config('services.ai').
 * Falls back to FakeAIReviewProvider when provider is 'fake' or unset.
 */
final class AIReviewProviderResolver
{
    /** Minimum allowed AI request timeout, in seconds. */
    public const MIN_TIMEOUT = 5;

    /** Maximum allowed AI request timeout, in seconds. */
    public const MAX_TIMEOUT = 120;

    /** Fallback timeout when the configured value is missing or invalid, in seconds. */
    public const DEFAULT_TIMEOUT = 30;

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

    /**
     * Normalize the configured timeout into a safe integer in [MIN_TIMEOUT, MAX_TIMEOUT].
     * Non-numeric, null, zero, or negative values fall back to DEFAULT_TIMEOUT.
     */
    public static function timeout(mixed $value): int
    {
        if ($value === null || ! is_numeric($value)) {
            return self::DEFAULT_TIMEOUT;
        }

        $seconds = (int) $value;

        if ($seconds < 1) {
            return self::MIN_TIMEOUT;
        }

        return max(self::MIN_TIMEOUT, min($seconds, self::MAX_TIMEOUT));
    }

    private static function openAICompatible(array $config): OpenAICompatibleReviewProvider
    {
        $baseUrl = $config['base_url'] ?? null;
        $key = $config['key'] ?? null;
        $model = $config['model'] ?? null;
        $endpoint = $config['endpoint'] ?? 'chat/completions';
        $timeout = self::timeout($config['timeout'] ?? null);

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new RuntimeException('The AI provider base_url is not configured.');
        }

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('The AI provider key is not configured.');
        }

        if (! is_string($model) || $model === '') {
            throw new RuntimeException('The AI provider model is not configured.');
        }

        return new OpenAICompatibleReviewProvider(
            new OpenAICompatibleResponseMapper(new AIReviewResponseValidator),
            $baseUrl,
            $endpoint,
            $key,
            $model,
            $timeout,
            self::connectTimeout($timeout),
        );
    }

    /**
     * Cap the connect phase at 10 seconds so a hung TCP handshake fails fast and
     * never waits on the full request timeout: a dead host should surface as a
     * quick connect error, while only slow-but-live HTTP responses consume the
     * configured per-request budget. 10s is well within typical DNS + TCP + TLS
     * setup time and keeps the effective connection floor bounded.
     */
    private static function connectTimeout(int $timeout): int
    {
        return min($timeout, 10);
    }
}
