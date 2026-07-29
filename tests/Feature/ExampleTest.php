<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_landing_page_displays_project_information(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('GitHub AI Reviewer')
            ->assertSee('Repository health, made clear.')
            ->assertSee('Currently in development')
            ->assertSee('Analyze Repository')
            ->assertSee('https://github.com/laravel/laravel');
    }
}
