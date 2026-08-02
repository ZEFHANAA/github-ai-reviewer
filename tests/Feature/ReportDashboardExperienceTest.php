<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_prioritizes_deterministic_improvements_and_keeps_ai_secondary(): void
    {
        $this->fakeGitHub();

        $response = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ]);

        $response
            ->assertOk()
            ->assertSee('Start with these deterministic improvements')
            ->assertSee('data-priority-actions', false)
            ->assertSee('Source: deterministic check')
            ->assertSeeInOrder(['Start with these deterministic improvements', 'Qualitative review'])
            ->assertSee('Analyze another repository');
    }

    public function test_report_exposes_category_scores_as_accessible_progress_bars(): void
    {
        $this->fakeGitHub();

        $body = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ])->assertOk()->getContent() ?: '';

        $this->assertStringContainsString('role="progressbar"', $body);
        $this->assertStringContainsString('aria-valuemin="0"', $body);
        $this->assertStringContainsString('aria-valuemax="100"', $body);
        $this->assertStringContainsString('aria-valuenow=', $body);
    }

    private function fakeGitHub(): void
    {
        $payload = json_decode(file_get_contents(base_path('tests/Fixtures/github-repository.json')) ?: '', true, 512, JSON_THROW_ON_ERROR);

        Http::fake([
            'api.github.com/repos/laravel/laravel' => Http::response($payload),
            'api.github.com/repos/laravel/laravel/contents/' => Http::response([
                ['path' => 'README.md'], ['path' => 'LICENSE'], ['path' => '.gitignore'], ['path' => 'composer.json'], ['path' => 'app'], ['path' => 'tests'],
            ]),
            'api.github.com/repos/laravel/laravel/contents*' => Http::response([]),
        ]);
    }
}
