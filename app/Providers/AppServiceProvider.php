<?php

namespace App\Providers;

use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\Providers\FakeAIReviewProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AIReviewProviderInterface::class, FakeAIReviewProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
