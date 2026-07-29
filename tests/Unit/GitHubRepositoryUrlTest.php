<?php

namespace Tests\Unit;

use App\ValueObjects\GitHubRepositoryUrl;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GitHubRepositoryUrlTest extends TestCase
{
    public function test_it_parses_a_canonical_github_repository_url(): void
    {
        $repository = GitHubRepositoryUrl::parse('https://github.com/laravel/laravel');

        $this->assertSame('laravel', $repository->owner);
        $this->assertSame('laravel', $repository->name);
        $this->assertSame('https://github.com/laravel/laravel', $repository->canonicalUrl);
    }

    public function test_it_normalizes_a_trailing_slash(): void
    {
        $repository = GitHubRepositoryUrl::parse('https://github.com/laravel/laravel/');

        $this->assertSame('https://github.com/laravel/laravel', $repository->canonicalUrl);
    }

    #[DataProvider('invalidUrls')]
    public function test_it_rejects_invalid_repository_urls(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        GitHubRepositoryUrl::parse($url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUrls(): array
    {
        return [
            'non-GitHub domain' => ['https://example.com/laravel/laravel'],
            'malformed URL' => ['not a URL'],
            'missing owner' => ['https://github.com/laravel'],
            'missing repository' => ['https://github.com/laravel/'],
            'extra path' => ['https://github.com/laravel/laravel/issues'],
            'query string' => ['https://github.com/laravel/laravel?tab=readme'],
            'fragment' => ['https://github.com/laravel/laravel#readme'],
            'git clone URL' => ['https://github.com/laravel/laravel.git'],
            'wrong scheme' => ['http://github.com/laravel/laravel'],
        ];
    }
}
