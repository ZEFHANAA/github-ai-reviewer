<?php

namespace Tests\Unit;

use App\ValueObjects\GitHubRepositoryMetadata;
use App\ValueObjects\RepositorySnapshot;
use PHPUnit\Framework\TestCase;

class RepositorySnapshotTest extends TestCase
{
    public function test_path_queries_are_case_insensitive_and_omitted_data_is_prefix_matched(): void
    {
        $snapshot = new RepositorySnapshot(
            $this->metadata(),
            ['readme.md', 'docs/guide.md'],
            omittedData: ['docs/']
        );

        $this->assertTrue($snapshot->has('README.MD'));
        $this->assertTrue($snapshot->starts('Docs'));
        $this->assertTrue($snapshot->isOmitted('docs'));
        $this->assertFalse($snapshot->isOmitted('unrelated'));
    }

    private function metadata(): GitHubRepositoryMetadata
    {
        return GitHubRepositoryMetadata::fromGitHubResponse([
            'full_name' => 'acme/project',
            'html_url' => 'https://github.com/acme/project',
            'owner' => ['login' => 'acme'],
            'name' => 'project',
            'description' => null,
            'default_branch' => 'main',
            'language' => 'PHP',
            'stargazers_count' => 0,
            'forks_count' => 0,
            'open_issues_count' => 0,
            'watchers_count' => 0,
            'subscribers_count' => null,
            'size' => 1,
            'created_at' => null,
            'updated_at' => null,
            'pushed_at' => null,
            'archived' => false,
            'fork' => false,
            'visibility' => 'public',
            'license' => null,
            'topics' => [],
        ]);
    }
}
