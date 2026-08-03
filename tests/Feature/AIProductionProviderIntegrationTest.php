<?php

namespace Tests\Feature;

use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\Providers\FakeAIReviewProvider;
use App\Services\AI\Providers\OpenAICompatibleResponseMapper;
use App\Services\AI\Providers\OpenAICompatibleReviewProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIProductionProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_openai_compatible_provider_produces_available_outcome(): void
    {
        $this->fakeGitHub();

        // Fake AI provider response
        Http::fake([
            'https://ai.example.test/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'repository_summary' => '[DOC-README-001] Well-structured repository with clear documentation practices.',
                        'documentation_observations' => [
                            '[DOC-README-001] README explains the project purpose and setup steps.',
                        ],
                        'maintainability_observations' => [
                            '[STRUCT-SOURCE-001] Source code follows a clear directory layout.',
                        ],
                        'potential_concerns' => [
                            '[SEC-ENV-001] Environment file is tracked in the repository.',
                        ],
                        'prioritized_recommendations' => [
                            '[GIT-CI-001] Consider adding CI workflow for automated testing.',
                        ],
                    ], JSON_THROW_ON_ERROR)],
                ]],
            ]),
        ]);

        $this->app->bind(AIReviewProviderInterface::class, fn (): OpenAICompatibleReviewProvider => new OpenAICompatibleReviewProvider(
            app(OpenAICompatibleResponseMapper::class),
            baseUrl: 'https://ai.example.test/v1',
            endpoint: 'chat/completions',
            key: 'sk-test-key',
            model: 'gpt-4o-mini',
            timeout: 30,
            connectTimeout: 10,
        ));

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('Overall Repository Health Score')
            ->assertSee('Category Scores')
            ->assertSee('Repository checks')
            ->assertSee('AI Review');

        // The [RULE-ID] prefix is stripped at render time, so assert the prose.
        $response->assertSee('README explains');
        $response->assertSee('CI workflow');

        // No invented Rule IDs surfaced
        $this->assertStringNotContainsString('[INVENTED-', $response->getContent());
    }

    public function test_misconfigured_provider_falls_back_to_fake(): void
    {
        $this->fakeGitHub();

        config()->set('services.ai', [
            'provider' => 'openai_compatible',
            'base_url' => 'https://ai.example.test/v1',
            'key' => '',
            'model' => '',
        ]);

        // Container must not crash — FakeAIReviewProvider as fallback
        $provider = $this->app->make(AIReviewProviderInterface::class);
        $this->assertInstanceOf(FakeAIReviewProvider::class, $provider);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('Overall Repository Health Score')
            ->assertSee('Deterministic checks');
    }

    public function test_ai_provider_timeout_falls_back_to_deterministic_only(): void
    {
        $this->fakeGitHub();

        $this->app->bind(AIReviewProviderInterface::class, fn (): OpenAICompatibleReviewProvider => new OpenAICompatibleReviewProvider(
            app(OpenAICompatibleResponseMapper::class),
            baseUrl: 'https://ai.example.test/v1',
            endpoint: 'chat/completions',
            key: 'sk-test-key',
            model: 'gpt-4o-mini',
            timeout: 5,
            connectTimeout: 5,
        ));

        // Provider will time out
        Http::fake([
            'https://ai.example.test/v1/*' => fn () => throw new ConnectionException('Connection timed out.'),
        ]);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('Overall Repository Health Score')
            ->assertSee('AI review is temporarily unavailable')
            ->assertSee('Category Scores')
            ->assertSee('Repository checks');
    }

    public function test_ai_provider_rejects_invented_rule_ids(): void
    {
        $this->fakeGitHub();

        config()->set('services.ai', [
            'provider' => 'openai_compatible',
            'base_url' => 'https://ai.example.test/v1',
            'key' => 'sk-test-key',
            'model' => 'gpt-4o-mini',
        ]);

        // Provider returns invented rule ID
        Http::fake([
            'https://ai.example.test/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'repository_summary' => '[INVENTED-RULE-999] This repository is awesome.',
                        'documentation_observations' => [],
                        'maintainability_observations' => [],
                        'potential_concerns' => [],
                        'prioritized_recommendations' => [],
                    ], JSON_THROW_ON_ERROR)],
                ]],
            ]),
        ]);

        $this->app->bind(AIReviewProviderInterface::class, fn (): OpenAICompatibleReviewProvider => new OpenAICompatibleReviewProvider(
            app(OpenAICompatibleResponseMapper::class),
            baseUrl: 'https://ai.example.test/v1',
            endpoint: 'chat/completions',
            key: 'sk-test-key',
            model: 'gpt-4o-mini',
            timeout: 30,
            connectTimeout: 10,
        ));

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('Overall Repository Health Score')
            ->assertSee('AI review is temporarily unavailable')
            ->assertDontSee('INVENTED-RULE-999');
    }

    private function fakeGitHub(array $overrides = []): void
    {
        $payload = array_merge(
            json_decode(
                file_get_contents(base_path('tests/Fixtures/github-repository.json')),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),
            $overrides
        );

        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($payload),
            'api.github.com/repos/laravel/laravel/contents/' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
        ]);
    }
}
