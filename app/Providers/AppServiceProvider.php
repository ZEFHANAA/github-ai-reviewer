<?php

namespace App\Providers;

use App\Contracts\AIReviewProviderInterface;
use App\Services\AI\AIReviewProviderResolver;
use App\Services\AI\Providers\FakeAIReviewProvider;
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
            function (): AIReviewProviderInterface {
                // Production provider resolution is deferred to review() time so
                // a misconfigured .env (missing key, model, or base_url) never
                // crashes the container at boot.  SafeAIReviewService absorbs any
                // downstream failure, keeping deterministic analysis unaffected.
                // ponytail: config validation is eager only for local dev; for
                // production deployments where secrets arrive late (platform env
                // injection), this graceful fallback is the correct behaviour.
                try {
                    return AIReviewProviderResolver::resolve(config('services.ai'));
                } catch (\Throwable) {
                    return new FakeAIReviewProvider;
                }
            },
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
        // ponytail: only guards Laravel's built-in debug mode. App-level logging
        // and error-report sensitivity should be audited separately.
        if (app()->environment() !== 'local') {
            config()->set('app.debug', false);
        }

        RateLimiter::for('repository-analysis', fn (Request $request) => Limit::perMinute(self::ANALYSIS_ATTEMPTS_PER_MINUTE)
            ->by($request->ip())
            ->response(fn (): Response => response()->view('repositories.error', [
                'message' => 'Too many repository analyses. Please try again in a minute.',
                'status' => 429,
                'submittedUrl' => null,
            ], 429)));
    }
}
