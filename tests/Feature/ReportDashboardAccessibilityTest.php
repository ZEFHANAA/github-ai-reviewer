<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportDashboardAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_progress_bar_is_clamped_to_zero_one_hundred(): void
    {
        $this->fakeGitHub();

        $body = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ])->assertOk()->getContent() ?: '';

        // aria-valuenow must never exceed 100
        preg_match_all('/aria-valuenow="(\d+)"/', $body, $matches);
        foreach ($matches[1] as $value) {
            $this->assertLessThanOrEqual(100, (int) $value, 'aria-valuenow must not exceed 100');
            $this->assertGreaterThanOrEqual(0, (int) $value, 'aria-valuenow must not be negative');
        }

        // aria-valuemax is exactly 100
        $this->assertStringContainsString('aria-valuemax="100"', $body);
        $this->assertStringContainsString('aria-valuemin="0"', $body);
    }

    public function test_filter_exposes_visible_count_for_screen_readers(): void
    {
        $this->fakeGitHub();

        $body = $this->post(route('repositories.submit'), [
            'repository_url' => 'https://github.com/laravel/laravel',
        ])->assertOk()->getContent() ?: '';

        $this->assertStringContainsString('data-filter-status', $body);
        $this->assertStringContainsString('data-filter-clear', $body);
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
