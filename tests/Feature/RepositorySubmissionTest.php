<?php

namespace Tests\Feature;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Contracts\AIReviewProviderInterface;
use App\Models\Analysis;
use App\Models\Finding;
use App\Models\Repository;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RepositorySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_repository_url_displays_normalized_github_metadata(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($this->repositoryPayload()),
            'api.github.com/repos/laravel/laravel/contents/' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'], ['path' => '.github'],
            ]),
            'api.github.com/repos/laravel/laravel/contents/.github' => Http::response([
                ['path' => '.github/workflows'], ['path' => '.github/dependabot.yml'],
            ]),
            'api.github.com/repos/laravel/laravel/contents/.github/workflows' => Http::response([
                ['path' => '.github/workflows/tests.yml'],
            ]),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
        ]);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel/',
        ]);

        $response
            ->assertOk()
            ->assertSee('Metadata retrieved from GitHub')
            ->assertSee('laravel/laravel')
            ->assertSee('https://github.com/laravel/laravel')
            ->assertSee('Laravel is a web application framework.')
            ->assertSee('MIT License')
            ->assertSee('Overall Repository Health Score')
            ->assertSee('Category Scores')
            ->assertSee('Repository checks')
            ->assertSee('Deterministic checks');
    }

    #[DataProvider('repositoryUrlRenderCases')]
    public function test_the_report_renders_only_https_repository_urls_as_active_links(string $url, bool $isActiveLink): void
    {
        $this->fakeGitHub(['html_url' => $url]);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $body = $response->assertOk()->getContent() ?: '';

        $this->assertStringContainsString(e($url), $body);

        if ($isActiveLink) {
            $this->assertStringContainsString('href="'.e($url).'"', $body);
        } else {
            $this->assertStringNotContainsString('href="'.e($url).'"', $body);
        }
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function repositoryUrlRenderCases(): array
    {
        return [
            'https' => ['https://github.com/laravel/laravel', true],
            'http' => ['http://github.com/laravel/laravel', false],
            'javascript' => ['javascript:alert(1)', false],
            'malformed' => ['not a valid URL', false],
        ];
    }

    public function test_invalid_repository_submission_redirects_back_with_the_input_and_error(): void
    {
        $response = $this->from(route('home'))->post(route('repositories.submit'), [
            'repository_url' => 'https://example.com/laravel/laravel',
        ]);

        $response
            ->assertRedirect(route('home'))
            ->assertInvalid('repository_url')
            ->assertSessionHasInput('repository_url', 'https://example.com/laravel/laravel');
    }

    public function test_a_missing_repository_displays_a_friendly_error(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response([], 404),
        ]);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertNotFound()
            ->assertSee('We could not find that public GitHub repository.')
            ->assertDontSee('stack trace');
    }

    public function test_the_report_marks_scores_as_deterministic_and_renders_findings_with_source_and_filter_markup(): void
    {
        $this->fakeGitHub();

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('Deterministic score')
            ->assertSee('AI-assisted qualitative review')
            ->assertSee('Clear filters')
            ->assertSee('No matching repository checks')
            ->assertSee('Filter by category')
            ->assertSee('Filter by severity')
            ->assertSee('Filter by status');
    }

    public function test_the_report_provides_data_attributes_for_client_side_filtering(): void
    {
        $this->fakeGitHub();

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $body = $response->getContent() ?: '';

        $this->assertStringContainsString('data-repository-filters', $body);
        $dataElement = collect(explode("\n", $body))->first(fn (string $line): bool => str_contains($line, 'data-repository-filters'));

        $this->assertNotNull($dataElement, 'Expected a [data-repository-filters] container for the finding filters.');
        $this->assertStringContainsString('data-filter-target="finding"', $body);
        $this->assertStringContainsString('data-finding-empty', $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function repositoryPayload(): array
    {
        $contents = file_get_contents(base_path('tests/Fixtures/github-repository.json'));

        return json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_the_report_displays_ai_enrichment_with_stripped_rule_references(): void
    {
        $this->fakeGitHub();
        $this->swap(AIReviewProviderInterface::class, new class implements AIReviewProviderInterface
        {
            public function review(AIReviewRequest $request): AIReviewResponse
            {
                $rule = '[DOC-README-001] ';

                return new AIReviewResponse(
                    $rule.'A mature framework skeleton with strong documentation.',
                    [$rule.'The README explains installation clearly.'],
                    [$rule.'Tests directory is present and organised.'],
                    [$rule.'Dependency updates are not automated.'],
                    [$rule.'Enable Dependabot for dependency updates.'],
                );
            }
        });

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('AI-assisted qualitative review')
            ->assertSee('A mature framework skeleton with strong documentation.')
            ->assertSee('The README explains installation clearly.')
            ->assertSee('Tests directory is present and organised.')
            ->assertSee('Dependency updates are not automated.')
            ->assertSee('Enable Dependabot for dependency updates.')
            ->assertSee('Overall Repository Health Score')
            ->assertDontSee('AI review is temporarily unavailable.');
    }

    public function test_an_ai_provider_failure_keeps_the_deterministic_report_intact(): void
    {
        $this->fakeGitHub();
        $this->swap(AIReviewProviderInterface::class, new class implements AIReviewProviderInterface
        {
            public function review(AIReviewRequest $request): AIReviewResponse
            {
                throw new Exception('provider offline');
            }
        });

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('AI review is temporarily unavailable.')
            ->assertSee('Overall Repository Health Score')
            ->assertSee('Category Scores')
            ->assertSee('Repository checks')
            ->assertSee('laravel/laravel');
    }

    public function test_submitting_a_repository_persists_the_analysis_and_exposes_the_record_in_the_view(): void
    {
        $this->fakeGitHub();

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $analysis = Analysis::with('repository', 'findings')->firstOrFail();
        $this->assertDatabaseCount('repositories', 1);
        $this->assertSame('laravel', $analysis->repository->owner);
        $this->assertNotEmpty($analysis->findings);
        $response->assertOk()->assertSee('data-analysis-id="'.$analysis->id.'"', false);
    }

    public function test_a_github_500_during_snapshot_mid_flight_returns_a_friendly_error_without_persisting(): void
    {
        // Metadata succeeds, but the root /contents/ listing returns 500.
        // Contract: GitHubRepositoryService maps non-404/429/403-exhausted failures to
        // GitHubServiceUnavailableException (status 503), which the controller catches and
        // renders via repositories.error. Persistence runs only after snapshot+analysis, so no
        // repository/analysis/finding rows should exist after the failure.
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($this->repositoryPayload()),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([], 500),
        ]);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertStatus(503)
            ->assertSee('We could not retrieve this repository.')
            ->assertSee('GitHub is temporarily unavailable. Please try again shortly.')
            ->assertDontSee('Overall Repository Health Score')
            ->assertDontSee('stack trace');

        $this->assertDatabaseCount('repositories', 0);
        $this->assertDatabaseCount('analyses', 0);
        $this->assertDatabaseCount('findings', 0);
    }

    public function test_a_rate_limited_submission_is_user_friendly_and_does_not_persist_new_records(): void
    {
        $this->fakeGitHub();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->post(route('repositories.submit'), ['repository_url' => 'https://github.com/laravel/laravel'])
                ->assertOk();
        }

        $repositories = Repository::count();
        $analyses = Analysis::count();
        $findings = Finding::count();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('repositories.submit'), ['repository_url' => 'https://github.com/laravel/laravel'])
            ->assertTooManyRequests()
            ->assertSee('Too many repository analyses. Please try again in a minute.');

        $this->assertDatabaseCount('repositories', $repositories);
        $this->assertDatabaseCount('analyses', $analyses);
        $this->assertDatabaseCount('findings', $findings);
    }

    private function fakeGitHub(array $overrides = []): void
    {
        $payload = array_merge($this->repositoryPayload(), $overrides);

        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($payload),
            'api.github.com/repos/laravel/laravel/contents/' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
        ]);
    }
}
