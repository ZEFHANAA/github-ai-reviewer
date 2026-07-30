<?php

namespace Tests\Feature;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Contracts\AIReviewProviderInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RepositorySubmissionTest extends TestCase
{
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

    public function test_the_report_displays_ai_enrichment_when_the_provider_succeeds(): void
    {
        $this->fakeGitHub();
        $this->swap(AIReviewProviderInterface::class, new class implements AIReviewProviderInterface
        {
            public function review(AIReviewRequest $request): AIReviewResponse
            {
                return new AIReviewResponse(
                    'A mature framework skeleton with strong documentation.',
                    ['The README explains installation clearly.'],
                    ['Tests directory is present and organised.'],
                    ['Dependency updates are not automated.'],
                    ['Enable Dependabot for dependency updates.'],
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

    private function fakeGitHub(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($this->repositoryPayload()),
            'api.github.com/repos/laravel/laravel/contents/' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
        ]);
    }
}
