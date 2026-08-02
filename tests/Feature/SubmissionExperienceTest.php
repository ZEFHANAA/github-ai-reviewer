<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubmissionExperienceTest extends TestCase
{
    public function test_the_landing_page_presents_the_product_instead_of_a_development_notice(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertDontSee('Currently in development')
            ->assertSee('Analyze a public GitHub repository');
    }

    public function test_the_submit_form_exposes_an_accessible_loading_state(): void
    {
        $body = $this->get(route('home'))->assertOk()->getContent() ?: '';

        $this->assertStringContainsString('data-analyze-form', $body);
        $this->assertStringContainsString('data-analyze-submit', $body);
        $this->assertStringContainsString('data-analyze-status', $body);
        $this->assertStringContainsString('aria-live="polite"', $body);
    }

    public function test_a_failed_analysis_offers_a_retry_prefilled_with_the_submitted_repository(): void
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
            ->assertSee('Check that the repository exists and is public')
            ->assertSee('value="https://github.com/laravel/laravel"', false)
            ->assertSee('Try again');
    }

    public function test_a_rate_limited_analysis_explains_how_to_recover(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response([], 429),
        ]);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertStatus(429)
            ->assertSee('Wait a minute before requesting another analysis');
    }

    public function test_an_unavailable_github_explains_how_to_recover(): void
    {
        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response([], 500),
        ]);

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertStatus(503)
            ->assertSee('GitHub is temporarily unavailable. Please try again shortly.')
            ->assertSee('This is a GitHub availability issue, not a problem with your repository');
    }
}
