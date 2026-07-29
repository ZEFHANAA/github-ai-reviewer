<?php

namespace App\Services\GitHub;

use App\Services\GitHub\Exceptions\GitHubRateLimitException;
use App\Services\GitHub\Exceptions\GitHubRepositoryNotFoundException;
use App\Services\GitHub\Exceptions\GitHubServiceUnavailableException;
use App\Services\GitHub\Exceptions\GitHubUnexpectedResponseException;
use App\ValueObjects\GitHubRepositoryMetadata;
use App\ValueObjects\GitHubRepositoryUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class GitHubRepositoryService
{
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
