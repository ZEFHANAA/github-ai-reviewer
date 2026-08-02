<?php

namespace App\Providers;

use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\AIReviewProviderResolver;
use App\Support\SecretRedactor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Repository analysis is expensive (GitHub + AI calls), so submissions are capped per client IP. */
    public const ANALYSIS_ATTEMPTS_PER_MINUTE = 10;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AIReviewProviderInterface::class,
            fn (): AIReviewProviderInterface => AIReviewProviderResolver::resolve(config('services.ai')),
        );
        $this->app->singleton(SecretRedactor::class, fn (): SecretRedactor => new SecretRedactor([
            config('services.ai.key'),
            config('services.github.token'),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('repository-analysis', fn (Request $request) => Limit::perMinute(self::ANALYSIS_ATTEMPTS_PER_MINUTE)
            ->by($request->ip())
            ->response(fn (): Response => response()->view('repositories.error', [
                'message' => 'Too many repository analyses. Please try again in a minute.',
                'status' => 429,
                'submittedUrl' => null,
            ], 429)));
    }
}
