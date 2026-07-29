<?php

namespace Tests\Feature;

use Tests\TestCase;

class RepositorySubmissionTest extends TestCase
{
    public function test_a_valid_repository_url_displays_the_normalized_repository_identity(): void
    {
        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel/',
        ]);

        $response
            ->assertOk()
            ->assertSee('Repository URL validated')
            ->assertSee('laravel/laravel')
            ->assertSee('https://github.com/laravel/laravel')
            ->assertSee('GitHub data has not been requested yet.');
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
}
