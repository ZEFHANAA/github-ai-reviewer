<?php

namespace Tests\Unit;

use App\Services\GitHub\Exceptions\GitHubRateLimitException;
use App\Services\GitHub\Exceptions\GitHubRepositoryNotFoundException;
use App\Services\GitHub\Exceptions\GitHubServiceUnavailableException;
use App\Services\GitHub\Exceptions\GitHubUnexpectedResponseException;
use App\Services\GitHub\GitHubRepositoryService;
use App\ValueObjects\GitHubRepositoryUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubRepositoryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.github.token', null);
    }

    public function test_it_retrieves_and_normalizes_repository_metadata(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($this->repositoryPayload()),
        ]);

        $metadata = app(GitHubRepositoryService::class)->fetchMetadata(
            GitHubRepositoryUrl::parse('https://github.com/laravel/laravel')
        );

        $this->assertSame('laravel/laravel', $metadata->fullName);
        $this->assertSame('Laravel is a web application framework.', $metadata->description);
        $this->assertSame('PHP', $metadata->primaryLanguage);
        $this->assertSame(25000, $metadata->starsCount);
        $this->assertSame('MIT License', $metadata->licenseName);
        $this->assertSame(['framework', 'laravel', 'php'], $metadata->topics);
        $this->assertSame('2026-07-28', $metadata->pushedAt?->toDateString());
    }

    public function test_it_sends_an_optional_token_when_configured(): void
    {
        config()->set('services.github.token', 'test-token');
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($this->repositoryPayload()),
        ]);

        app(GitHubRepositoryService::class)->fetchMetadata(
            GitHubRepositoryUrl::parse('https://github.com/laravel/laravel')
        );

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.github.com/repos/laravel/laravel'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('X-GitHub-Api-Version', '2022-11-28');
        });
    }

    public function test_it_handles_a_repository_not_found_response(): void
    {
        Http::fake([
            'api.github.com/repos/*' => Http::response([], 404),
        ]);

        $this->expectException(GitHubRepositoryNotFoundException::class);

        app(GitHubRepositoryService::class)->fetchMetadata(
            GitHubRepositoryUrl::parse('https://github.com/laravel/laravel')
        );
    }

    public function test_it_handles_a_rate_limit_response(): void
    {
        Http::fake([
            'api.github.com/repos/*' => Http::response([], 403, ['X-RateLimit-Remaining' => '0']),
        ]);

        $this->expectException(GitHubRateLimitException::class);

        app(GitHubRepositoryService::class)->fetchMetadata(
            GitHubRepositoryUrl::parse('https://github.com/laravel/laravel')
        );
    }

    public function test_it_handles_a_connection_failure(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('Connection failed.');
        });

        $this->expectException(GitHubServiceUnavailableException::class);

        app(GitHubRepositoryService::class)->fetchMetadata(
            GitHubRepositoryUrl::parse('https://github.com/laravel/laravel')
        );
    }

    public function test_it_handles_an_unexpected_response(): void
    {
        Http::fake([
            'api.github.com/repos/*' => Http::response(['name' => 'laravel']),
        ]);

        $this->expectException(GitHubUnexpectedResponseException::class);

        app(GitHubRepositoryService::class)->fetchMetadata(
            GitHubRepositoryUrl::parse('https://github.com/laravel/laravel')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function repositoryPayload(): array
    {
        $contents = file_get_contents(base_path('tests/Fixtures/github-repository.json'));

        return json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
    }
}
