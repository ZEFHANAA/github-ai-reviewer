<?php

namespace Tests\Unit\AI;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Analysis\FinalScoreCalculator;
use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\AIReviewPromptBuilder;
use App\Services\AI\AIReviewResponseValidator;
use App\Services\AI\AIReviewService;
use App\Services\AI\Providers\FakeAIReviewProvider;
use App\Services\AI\Providers\OpenAICompatibleResponseMapper;
use App\Services\AI\Providers\OpenAICompatibleReviewProvider;
use App\Services\AI\SafeAIReviewService;
use App\ValueObjects\GitHubRepositoryMetadata;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use Psr\Log\NullLogger;
use Tests\TestCase;
use UnexpectedValueException;

class OpenAICompatibleReviewProviderTest extends TestCase
{
    public function test_it_sends_an_openai_compatible_request_and_returns_a_valid_response(): void
    {
        Http::fake([
            'https://ai.example.test/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($this->responsePayload(), JSON_THROW_ON_ERROR)],
                ]],
            ]),
        ]);

        $response = $this->provider()->review($this->request());

        $this->assertInstanceOf(AIReviewResponse::class, $response);
        $this->assertSame($this->responsePayload(), $response->toArray());
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://ai.example.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['model'] === 'test-model'
                && $request['messages'][0]['content'] === 'Review this repository.';
        });
    }

    public function test_it_throws_on_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out.'));

        $this->expectException(ConnectionException::class);

        $this->provider()->review($this->request());
    }

    public function test_it_throws_on_http_error(): void
    {
        Http::fake(['*' => Http::response(['error' => 'unavailable'], 503)]);

        $this->expectException(RequestException::class);

        $this->provider()->review($this->request());
    }

    public function test_it_throws_on_malformed_response_content(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [['message' => ['content' => 'not json']]],
            ]),
        ]);

        $this->expectException(JsonException::class);

        $this->provider()->review($this->request());
    }

    public function test_it_throws_when_the_response_has_no_content(): void
    {
        Http::fake(['*' => Http::response(['choices' => []])]);

        $this->expectException(UnexpectedValueException::class);

        $this->provider()->review($this->request());
    }

    public function test_the_safe_service_returns_unavailable_when_the_provider_fails(): void
    {
        Http::fake(['*' => Http::response(['error' => 'unavailable'], 503)]);

        $service = new SafeAIReviewService(
            new AIReviewService($this->provider(), new AIReviewPromptBuilder),
            new AIReviewResponseValidator,
            new NullLogger,
        );

        $outcome = $service->review($this->metadata(), (new FinalScoreCalculator)->report([]));

        $this->assertFalse($outcome->isAvailable);
    }

    public function test_the_container_resolves_the_provider_from_config(): void
    {
        config()->set('services.ai', [
            'provider' => 'openai_compatible',
            'base_url' => 'https://ai.example.test/v1',
            'endpoint' => 'chat/completions',
            'key' => 'test-key',
            'model' => 'test-model',
            'timeout' => 12,
        ]);

        $this->assertInstanceOf(OpenAICompatibleReviewProvider::class, $this->app->make(AIReviewProviderInterface::class));
    }

    public function test_the_container_falls_back_to_the_fake_provider(): void
    {
        config()->set('services.ai.provider', 'fake');

        $this->assertInstanceOf(FakeAIReviewProvider::class, $this->app->make(AIReviewProviderInterface::class));
    }

    private function provider(): OpenAICompatibleReviewProvider
    {
        return new OpenAICompatibleReviewProvider(
            new OpenAICompatibleResponseMapper(new AIReviewResponseValidator),
            baseUrl: 'https://ai.example.test/v1',
            endpoint: 'chat/completions',
            key: 'test-key',
            model: 'test-model',
            timeout: 12,
        );
    }

    private function request(): AIReviewRequest
    {
        return new AIReviewRequest(
            $this->metadata(),
            (new FinalScoreCalculator)->report([]),
            'Review this repository.',
        );
    }

    private function metadata(): GitHubRepositoryMetadata
    {
        $payload = json_decode(
            file_get_contents(base_path('tests/Fixtures/github-repository.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return GitHubRepositoryMetadata::fromGitHubResponse($payload);
    }

    /**
     * @return array{repository_summary: string, documentation_observations: list<string>, maintainability_observations: list<string>, potential_concerns: list<string>, prioritized_recommendations: list<string>}
     */
    private function responsePayload(): array
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
