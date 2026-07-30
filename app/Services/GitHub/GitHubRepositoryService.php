<?php

namespace App\Services\GitHub;

use App\Services\GitHub\Exceptions\GitHubRateLimitException;
use App\Services\GitHub\Exceptions\GitHubRepositoryNotFoundException;
use App\Services\GitHub\Exceptions\GitHubServiceUnavailableException;
use App\Services\GitHub\Exceptions\GitHubUnexpectedResponseException;
use App\ValueObjects\GitHubRepositoryMetadata;
use App\ValueObjects\GitHubRepositoryUrl;
use App\ValueObjects\RepositorySnapshot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class GitHubRepositoryService
{
    /**
     * Maximum Contents API requests allowed during a single snapshot collection
     * (1 request for root listing + up to 3 requests for subdirectories like .github, .github/workflows, docs).
     * Note: Metadata API endpoint (/repos/{owner}/{repo}) is separate and not counted in this limit.
     */
    public const MAX_CONTENT_REQUESTS = 4;

    /** Maximum entries accepted from any one directory listing. */
    public const MAX_DIRECTORY_ENTRIES = 200;

    public function fetchMetadata(GitHubRepositoryUrl $repository): GitHubRepositoryMetadata
    {
        try {
            $response = $this->client()->get("repos/{$repository->owner}/{$repository->name}");
        } catch (ConnectionException) {
            throw new GitHubServiceUnavailableException;
        }

        $this->throwForErrorResponse($response);

        try {
            $payload = $response->json();

            if (! is_array($payload)) {
                throw new InvalidArgumentException;
            }

            return GitHubRepositoryMetadata::fromGitHubResponse($payload);
        } catch (InvalidArgumentException) {
            throw new GitHubUnexpectedResponseException;
        }
    }

    public function collectSnapshot(GitHubRepositoryUrl $repository, GitHubRepositoryMetadata $metadata): RepositorySnapshot
    {
        $paths = $this->contents($repository, '', $metadata->defaultBranch);
        $unavailable = [];
        $omitted = [];
        $requests = 1;

        if ($requests < self::MAX_CONTENT_REQUESTS && in_array('.github', $paths, true)) {
            try {
                $githubPaths = $this->contents($repository, '.github', $metadata->defaultBranch);
                $paths = [...$paths, ...$githubPaths];
                $requests++;

                // Subdirectory priority list within .github:
                // Priority 1: .github/workflows (CI, CD, CodeQL, security scanning)
                // Priority 2: .github/ISSUE_TEMPLATE (Issue templates & forms)
                $subdirectories = ['.github/workflows', '.github/ISSUE_TEMPLATE'];

                foreach ($subdirectories as $subdir) {
                    if ($requests >= self::MAX_CONTENT_REQUESTS) {
                        $omitted[$subdir] = true;
                        break;
                    }

                    if (in_array($subdir, $githubPaths, true)) {
                        try {
                            $paths = [...$paths, ...$this->contents($repository, $subdir, $metadata->defaultBranch)];
                            $requests++;
                        } catch (\Throwable) {
                            $unavailable[] = $subdir;
                        }
                    }
                }
            } catch (\Throwable) {
                $unavailable[] = '.github';
            }
        }

        if ($requests >= self::MAX_CONTENT_REQUESTS && in_array('docs', $paths, true)) {
            $omitted['docs'] = true;
        }

        if ($requests < self::MAX_CONTENT_REQUESTS && in_array('docs', $paths, true)) {
            try {
                $paths = [...$paths, ...$this->contents($repository, 'docs', $metadata->defaultBranch)];
                $requests++;
            } catch (\Throwable) {
                $unavailable[] = 'docs';
            }
        }

        return new RepositorySnapshot($metadata, array_values(array_unique($paths)), $unavailable, array_keys($omitted));
    }

    /** @return list<string> */
    private function contents(GitHubRepositoryUrl $repository, string $path, ?string $branch): array
    {
        try {
            $response = $this->client()->get('repos/'.$repository->owner.'/'.$repository->name.'/contents/'.$path, ['ref' => $branch ?? 'HEAD']);
        } catch (ConnectionException) {
            throw new GitHubServiceUnavailableException;
        }
        $this->throwForErrorResponse($response);
        $payload = $response->json();
        if (! is_array($payload) || ! array_is_list($payload)) {
            throw new GitHubUnexpectedResponseException;
        }
        $paths = [];
        foreach (array_slice($payload, 0, self::MAX_DIRECTORY_ENTRIES) as $entry) {
            if (is_array($entry) && isset($entry['path']) && is_string($entry['path'])) {
                $paths[] = $entry['path'];
            }
        }

        return $paths;
    }

    private function client(): PendingRequest
    {
        $client = Http::baseUrl('https://api.github.com')
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => config('app.name'),
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->connectTimeout(5)
            ->timeout(10);

        $token = config('services.github.token');

        return filled($token) ? $client->withToken($token) : $client;
    }

    private function throwForErrorResponse(Response $response): void
    {
        if ($response->status() === 404) {
            throw new GitHubRepositoryNotFoundException;
        }

        if ($response->status() === 429 || ($response->status() === 403 && $response->header('X-RateLimit-Remaining') === '0')) {
            throw new GitHubRateLimitException;
        }

        if (! $response->successful()) {
            throw new GitHubServiceUnavailableException;
        }
    }
}
