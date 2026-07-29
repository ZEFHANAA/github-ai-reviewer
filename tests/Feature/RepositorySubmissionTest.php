<?php

namespace Tests\Feature;

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

    /**
     * @return array<string, mixed>
     */
    private function repositoryPayload(): array
    {
        $contents = file_get_contents(base_path('tests/Fixtures/github-repository.json'));

        return json_decode($contents ?: '', true, 512, JSON_THROW_ON_ERROR);
    }
}
