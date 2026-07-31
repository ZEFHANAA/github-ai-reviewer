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

    public function test_owner_length_boundary_accepts_39_characters_and_rejects_40_characters(): void
    {
        $owner39 = 'a'.str_repeat('b', 37).'c';
        $owner40 = 'a'.str_repeat('b', 38).'c';

        $this->assertSame($owner39, GitHubRepositoryUrl::parse("https://github.com/{$owner39}/repository")->owner);

        $this->expectException(InvalidArgumentException::class);
        GitHubRepositoryUrl::parse("https://github.com/{$owner40}/repository");
    }

    public function test_it_preserves_owner_and_name_case_in_the_canonical_url(): void
    {
        $repository = GitHubRepositoryUrl::parse('https://github.com/Laravel/Framework');

        $this->assertSame('Laravel', $repository->owner);
        $this->assertSame('Framework', $repository->name);
        $this->assertSame('https://github.com/Laravel/Framework', $repository->canonicalUrl);
    }

    public function test_it_accepts_a_repository_name_containing_dots(): void
    {
        $repository = GitHubRepositoryUrl::parse('https://github.com/octocat/my.repo');

        $this->assertSame('my.repo', $repository->name);
        $this->assertSame('https://github.com/octocat/my.repo', $repository->canonicalUrl);
    }

    public function test_it_accepts_a_name_containing_dot_git_that_is_not_the_suffix(): void
    {
        $repository = GitHubRepositoryUrl::parse('https://github.com/octocat/repo.git.backup');

        $this->assertSame('repo.git.backup', $repository->name);
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
