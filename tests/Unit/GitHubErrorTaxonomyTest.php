<?php

namespace Tests\Unit;

use App\Services\GitHub\Exceptions\GitHubRateLimitException;
use App\Services\GitHub\Exceptions\GitHubRepositoryException;
use App\Services\GitHub\Exceptions\GitHubServiceUnavailableException;
use App\Services\GitHub\GitHubRepositoryService;
use App\ValueObjects\GitHubRepositoryUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubErrorTaxonomyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.github.token', null);
    }

    private function repository(): GitHubRepositoryUrl
    {
        return GitHubRepositoryUrl::parse('https://github.com/laravel/laravel');
    }

    public function test_http_429_maps_to_rate_limit_exception(): void
    {
        Http::fake([
            'api.github.com/repos/*' => Http::response([], 429),
        ]);

        try {
            app(GitHubRepositoryService::class)->fetchMetadata($this->repository());
            $this->fail('Expected GitHubRateLimitException was not thrown.');
        } catch (GitHubRepositoryException $e) {
            $this->assertSame(GitHubRateLimitException::class, $e::class);
            $this->assertSame(429, $e->status());
            $this->assertSame('GitHub API rate limits have been reached. Please try again later.', $e->getMessage());
        }
    }

    public function test_http_403_with_zero_rate_limit_remaining_maps_to_rate_limit_exception(): void
    {
        Http::fake([
            'api.github.com/repos/*' => Http::response([], 403, ['X-RateLimit-Remaining' => '0']),
        ]);

        try {
            app(GitHubRepositoryService::class)->fetchMetadata($this->repository());
            $this->fail('Expected GitHubRateLimitException was not thrown.');
        } catch (GitHubRepositoryException $e) {
            $this->assertSame(GitHubRateLimitException::class, $e::class);
            $this->assertSame(429, $e->status());
            $this->assertSame('GitHub API rate limits have been reached. Please try again later.', $e->getMessage());
        }
    }

    public function test_http_403_without_rate_limit_header_maps_to_service_unavailable(): void
    {
        Http::fake([
            'api.github.com/repos/*' => Http::response([], 403),
        ]);

        try {
            app(GitHubRepositoryService::class)->fetchMetadata($this->repository());
            $this->fail('Expected GitHubServiceUnavailableException was not thrown.');
        } catch (GitHubRepositoryException $e) {
            $this->assertSame(GitHubServiceUnavailableException::class, $e::class);
            $this->assertSame(503, $e->status());
            $this->assertSame('GitHub is temporarily unavailable. Please try again shortly.', $e->getMessage());
        }
    }

    public function test_http_500_maps_to_service_unavailable(): void
    {
        Http::fake([
            'api.github.com/repos/*' => Http::response([], 500),
        ]);

        try {
            app(GitHubRepositoryService::class)->fetchMetadata($this->repository());
            $this->fail('Expected GitHubServiceUnavailableException was not thrown.');
        } catch (GitHubRepositoryException $e) {
            $this->assertSame(GitHubServiceUnavailableException::class, $e::class);
            $this->assertSame(503, $e->status());
            $this->assertSame('GitHub is temporarily unavailable. Please try again shortly.', $e->getMessage());
        }
    }

    public function test_connection_failure_maps_to_service_unavailable(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('Connection failed.');
        });

        try {
            app(GitHubRepositoryService::class)->fetchMetadata($this->repository());
            $this->fail('Expected GitHubServiceUnavailableException was not thrown.');
        } catch (GitHubRepositoryException $e) {
            $this->assertSame(GitHubServiceUnavailableException::class, $e::class);
            $this->assertSame(503, $e->status());
            $this->assertSame('GitHub is temporarily unavailable. Please try again shortly.', $e->getMessage());
        }
    }

    public function test_http_403_with_remaining_quota_still_maps_to_service_unavailable(): void
    {
        // The header is present but non-zero: this is not a rate-limit exhaustion,
        // so it must fall through to the generic non-success branch.
        Http::fake([
            'api.github.com/repos/*' => Http::response([], 403, ['X-RateLimit-Remaining' => '5']),
        ]);

        try {
            app(GitHubRepositoryService::class)->fetchMetadata($this->repository());
            $this->fail('Expected GitHubServiceUnavailableException was not thrown.');
        } catch (GitHubRepositoryException $e) {
            $this->assertSame(GitHubServiceUnavailableException::class, $e::class);
            $this->assertSame(503, $e->status());
            $this->assertSame('GitHub is temporarily unavailable. Please try again shortly.', $e->getMessage());
        }
    }
}
