<?php

namespace App\Providers;

use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\AIReviewProviderResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AIReviewProviderInterface::class,
            fn (): AIReviewProviderInterface => AIReviewProviderResolver::resolve(config('services.ai')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
