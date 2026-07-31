<?php

namespace Tests\Unit\AI;

use App\Services\AI\AIReviewProviderResolver;
use App\Services\AI\Providers\OpenAICompatibleReviewProvider;
use Tests\TestCase;

class AIReviewProviderResolverTest extends TestCase
{
    public function test_it_clamps_an_oversized_timeout_to_the_maximum(): void
    {
        $provider = $this->resolve(['timeout' => 9999]);

        $this->assertSame(AIReviewProviderResolver::MAX_TIMEOUT, $provider->timeout());
    }

    public function test_it_clamps_a_timeout_below_one_to_the_minimum(): void
    {
        $provider = $this->resolve(['timeout' => 0]);

        $this->assertSame(AIReviewProviderResolver::MIN_TIMEOUT, $provider->timeout());
    }

    public function test_it_clamps_a_negative_timeout_to_the_minimum(): void
    {
        $provider = $this->resolve(['timeout' => -10]);

        $this->assertSame(AIReviewProviderResolver::MIN_TIMEOUT, $provider->timeout());
    }

    public function test_it_falls_back_to_the_default_when_timeout_is_missing(): void
    {
        $provider = $this->resolve([]);

        $this->assertSame(AIReviewProviderResolver::DEFAULT_TIMEOUT, $provider->timeout());
    }

    public function test_it_falls_back_to_the_default_when_timeout_is_not_numeric(): void
    {
        $provider = $this->resolve(['timeout' => 'not-a-number']);

        $this->assertSame(AIReviewProviderResolver::DEFAULT_TIMEOUT, $provider->timeout());
    }

    public function test_it_accepts_a_valid_timeout_as_is(): void
    {
        $provider = $this->resolve(['timeout' => 45]);

        $this->assertSame(45, $provider->timeout());
    }

    public function test_it_sets_a_connect_timeout_separate_from_the_request_timeout(): void
    {
        $provider = $this->resolve(['timeout' => 60]);

        $this->assertLessThan($provider->timeout(), $provider->connectTimeout());
        $this->assertGreaterThanOrEqual(AIReviewProviderResolver::MIN_TIMEOUT, $provider->connectTimeout());
    }

    public function test_the_connect_timeout_never_exceeds_the_request_timeout(): void
    {
        $provider = $this->resolve(['timeout' => AIReviewProviderResolver::MIN_TIMEOUT]);

        $this->assertLessThanOrEqual($provider->timeout(), $provider->connectTimeout());
    }

    private function resolve(array $overrides): OpenAICompatibleReviewProvider
    {
        return AIReviewProviderResolver::resolve(array_merge([
            'provider' => 'openai_compatible',
            'base_url' => 'https://ai.example.test/v1',
            'key' => 'test-key',
            'model' => 'test-model',
        ], $overrides));
    }
}
