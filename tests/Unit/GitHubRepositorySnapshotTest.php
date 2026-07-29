<?php

namespace Tests\Unit;

use App\Services\GitHub\GitHubRepositoryService;
use App\ValueObjects\GitHubRepositoryUrl;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubRepositorySnapshotTest extends TestCase
{
    private function payload(): array
    {
        $p = json_decode(file_get_contents(base_path('tests/Fixtures/github-repository.json')), true, 512, JSON_THROW_ON_ERROR);
        $p['default_branch'] = 'main';

        return $p;
    }

    public function test_collect_snapshot_fetches_docs_when_present(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($this->payload()),
            'api.github.com/repos/laravel/laravel/contents/' => Http::response([
                ['path' => '.github'], ['path' => 'docs'], ['path' => 'src'],
            ]),
            'api.github.com/repos/laravel/laravel/contents/.github*' => Http::response([
                ['path' => '.github/workflows'],
            ]),
            'api.github.com/repos/laravel/laravel/contents/.github/workflows*' => Http::response([
                ['path' => '.github/workflows/ci.yml'],
            ]),
            'api.github.com/repos/laravel/laravel/contents/docs*' => Http::response([
                ['path' => 'docs/CONTRIBUTING.md'],
            ]),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([
                ['path' => '.github'], ['path' => 'docs'], ['path' => 'src'],
            ]),
        ]);

        $service = app(GitHubRepositoryService::class);
        $url = GitHubRepositoryUrl::parse('https://github.com/laravel/laravel');
        $metadata = $service->fetchMetadata($url);
        $snapshot = $service->collectSnapshot($url, $metadata);

        $this->assertContains('docs/CONTRIBUTING.md', $snapshot->paths);
    }

    public function test_unavailable_directories_are_marked(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($this->payload()),
            'api.github.com/repos/laravel/laravel/contents/docs*' => Http::response([], 500),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([
                ['path' => 'docs'],
            ]),
        ]);

        $service = app(GitHubRepositoryService::class);
        $url = GitHubRepositoryUrl::parse('https://github.com/laravel/laravel');
        $metadata = $service->fetchMetadata($url);
        $snapshot = $service->collectSnapshot($url, $metadata);

        $this->assertContains('docs', $snapshot->unavailableData);
    }

    public function test_request_budget_is_bounded_to_max_constant(): void
    {
        $this->assertSame(4, GitHubRepositoryService::MAX_CONTENT_REQUESTS);
        $this->assertSame(200, GitHubRepositoryService::MAX_DIRECTORY_ENTRIES);
    }

    public function test_collection_never_exceeds_max_content_requests(): void
    {
        $contentsCalls = 0;

        Http::fake(function (Request $request) use (&$contentsCalls) {
            $url = $request->url();

            if (str_contains($url, '/repos/laravel/laravel') && ! str_contains($url, '/contents')) {
                return Http::response($this->payload());
            }

            if (str_contains($url, '/contents')) {
                $contentsCalls++;

                return Http::response([
                    ['path' => '.github'],
                    ['path' => '.github/workflows'],
                    ['path' => 'docs'],
                    ['path' => 'deeply/nested/path/that/would/cause/many/requests'],
                ]);
            }

            return Http::response([], 404);
        });

        $service = app(GitHubRepositoryService::class);
        $url = GitHubRepositoryUrl::parse('https://github.com/laravel/laravel');
        $metadata = $service->fetchMetadata($url);
        $service->collectSnapshot($url, $metadata);

        $this->assertLessThanOrEqual(GitHubRepositoryService::MAX_CONTENT_REQUESTS, $contentsCalls);
    }
}
