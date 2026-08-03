<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_missing_page_uses_branded_not_found_page(): void
    {
        $this->get('/missing-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Back to repository review');
    }

    public function test_server_error_uses_branded_safe_error_page(): void
    {
        Route::get('/test-server-error', function (): void {
            abort(500);
        });

        $this->get('/test-server-error')
            ->assertInternalServerError()
            ->assertSee('Something went wrong')
            ->assertSee('Back to repository review')
            ->assertDontSee('stack trace');
    }

    public function test_laravel_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_debug_mode_forced_off_outside_local_environment(): void
    {
        $this->app['config']->set('app.debug', true);
        $this->assertEquals(true, config('app.debug'));

        // Reboot provider in a non-local environment to apply the guard.
        $this->app['env'] = 'production';
        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertFalse(config('app.debug'));
    }
}
