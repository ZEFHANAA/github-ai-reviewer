<?php

namespace Tests\Unit;

use App\Services\GitHub\Exceptions\GitHubRepositoryException;
use App\Services\GitHub\Exceptions\GitHubUnexpectedResponseException;
use App\Services\GitHub\GitHubRepositoryService;
use App\ValueObjects\GitHubRepositoryMetadata;
use App\ValueObjects\GitHubRepositoryUrl;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubRepositorySnapshotBranchesTest extends TestCase
{
    private function repository(): GitHubRepositoryUrl
    {
        return GitHubRepositoryUrl::parse('https://github.com/laravel/laravel');
    }

    private function metadata(): GitHubRepositoryMetadata
    {
        $contents = file_get_contents(base_path('tests/Fixtures/github-repository.json'));
        $payload = json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
        $payload['default_branch'] = 'main';

        return GitHubRepositoryMetadata::fromGitHubResponse($payload);
    }

    /**
     * @param  array<string, list<array{path: string}>|array<string, never>>  $contentsByPath
     */
    private function fakeContents(array $contentsByPath): void
    {
        Http::fake(function (Request $request) use ($contentsByPath) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $prefix = '/repos/laravel/laravel/contents/';

            if (str_starts_with($path, $prefix)) {
                $directory = substr($path, strlen($prefix));

                return Http::response($contentsByPath[$directory] ?? []);
            }

            return Http::response([], 404);
        });
    }

    public function test_workflows_are_collected_when_issue_template_is_missing(): void
    {
        $this->fakeContents([
            '' => [
                ['path' => '.github'],
                ['path' => 'docs'],
            ],
            '.github' => [
                ['path' => '.github/workflows'],
            ],
            '.github/workflows' => [
                ['path' => '.github/workflows/ci.yml'],
            ],
            'docs' => [
                ['path' => 'docs/CONTRIBUTING.md'],
            ],
        ]);

        $snapshot = app(GitHubRepositoryService::class)->collectSnapshot($this->repository(), $this->metadata());

        $this->assertSame([
            '.github',
            'docs',
            '.github/workflows',
            '.github/workflows/ci.yml',
            'docs/CONTRIBUTING.md',
        ], $snapshot->paths);
        $this->assertSame([], $snapshot->unavailableData);
        $this->assertSame([], $snapshot->omittedData);
    }

    public function test_priority_subdirectories_exhaust_budget_and_omit_docs(): void
    {
        $this->fakeContents([
            '' => [
                ['path' => '.github'],
                ['path' => 'docs'],
            ],
            '.github' => [
                ['path' => '.github/workflows'],
                ['path' => '.github/ISSUE_TEMPLATE'],
            ],
            '.github/workflows' => [
                ['path' => '.github/workflows/ci.yml'],
            ],
            '.github/ISSUE_TEMPLATE' => [
                ['path' => '.github/ISSUE_TEMPLATE/bug_report.md'],
            ],
            'docs' => [
                ['path' => 'docs/SHOULD-NOT-BE-COLLECTED.md'],
            ],
        ]);

        $snapshot = app(GitHubRepositoryService::class)->collectSnapshot($this->repository(), $this->metadata());

        $this->assertSame([
            '.github',
            'docs',
            '.github/workflows',
            '.github/ISSUE_TEMPLATE',
            '.github/workflows/ci.yml',
            '.github/ISSUE_TEMPLATE/bug_report.md',
        ], $snapshot->paths);
        $this->assertSame([], $snapshot->unavailableData);
        $this->assertSame(['docs'], $snapshot->omittedData);
        $this->assertTrue($snapshot->isOmitted('docs'));
        $this->assertFalse($snapshot->has('docs/SHOULD-NOT-BE-COLLECTED.md'));
    }

    public function test_object_payload_for_directory_listing_maps_to_unexpected_response(): void
    {
        $this->fakeContents([
            '' => ['path' => 'not-a-list'],
        ]);

        try {
            app(GitHubRepositoryService::class)->collectSnapshot($this->repository(), $this->metadata());
            $this->fail('Expected GitHubUnexpectedResponseException was not thrown.');
        } catch (GitHubRepositoryException $e) {
            $this->assertSame(GitHubUnexpectedResponseException::class, $e::class);
            $this->assertSame(502, $e->status());
            $this->assertSame('GitHub returned an unexpected repository response. Please try again later.', $e->getMessage());
        }
    }

    public function test_directory_entries_are_truncated_at_the_exact_limit_deterministically(): void
    {
        $entries = array_map(
            fn (int $number) => ['path' => sprintf('entry-%03d.txt', $number)],
            range(1, GitHubRepositoryService::MAX_DIRECTORY_ENTRIES + 2)
        );
        $expectedPaths = array_map(fn (int $number) => sprintf('entry-%03d.txt', $number), range(1, 200));

        $this->fakeContents(['' => $entries]);
        $service = app(GitHubRepositoryService::class);

        $first = $service->collectSnapshot($this->repository(), $this->metadata());
        $second = $service->collectSnapshot($this->repository(), $this->metadata());

        $this->assertSame(200, GitHubRepositoryService::MAX_DIRECTORY_ENTRIES);
        $this->assertSame($expectedPaths, $first->paths);
        $this->assertSame($first->paths, $second->paths);
        $this->assertFalse($first->has('entry-201.txt'));
        $this->assertFalse($first->has('entry-202.txt'));
    }

    public function test_a_failing_github_directory_is_marked_unavailable_without_aborting(): void
    {
        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $prefix = '/repos/laravel/laravel/contents/';
            $directory = substr($path, strlen($prefix));

            if ($directory === '.github') {
                return Http::response([], 500);
            }

            return Http::response(match ($directory) {
                '' => [['path' => '.github'], ['path' => 'docs']],
                'docs' => [['path' => 'docs/CONTRIBUTING.md']],
                default => [],
            });
        });

        $snapshot = app(GitHubRepositoryService::class)->collectSnapshot($this->repository(), $this->metadata());

        $this->assertSame(['.github'], $snapshot->unavailableData);
        $this->assertSame([], $snapshot->omittedData);
        // The .github failure must not stop docs from being collected.
        $this->assertSame(['.github', 'docs', 'docs/CONTRIBUTING.md'], $snapshot->paths);
    }

    public function test_a_failing_priority_subdirectory_is_marked_unavailable(): void
    {
        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $prefix = '/repos/laravel/laravel/contents/';
            $directory = substr($path, strlen($prefix));

            if ($directory === '.github/workflows') {
                return Http::response([], 500);
            }

            return Http::response(match ($directory) {
                '' => [['path' => '.github']],
                '.github' => [['path' => '.github/workflows'], ['path' => '.github/ISSUE_TEMPLATE']],
                '.github/ISSUE_TEMPLATE' => [['path' => '.github/ISSUE_TEMPLATE/bug_report.md']],
                default => [],
            });
        });

        $snapshot = app(GitHubRepositoryService::class)->collectSnapshot($this->repository(), $this->metadata());

        $this->assertSame(['.github/workflows'], $snapshot->unavailableData);
        $this->assertSame([], $snapshot->omittedData);
        // A failed workflows fetch does not consume budget, so ISSUE_TEMPLATE is still collected.
        $this->assertSame([
            '.github',
            '.github/workflows',
            '.github/ISSUE_TEMPLATE',
            '.github/ISSUE_TEMPLATE/bug_report.md',
        ], $snapshot->paths);
    }
}
