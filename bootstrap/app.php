<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Honour X-Forwarded-* headers so generated URLs (Vite assets, url()) use
        // the public https origin when running behind a TLS-terminating proxy such
        // as GitHub Codespaces port-forwarding or Render. Without this the app emits
        // the internal http://127.0.0.1:PORT origin, which the browser blocks as
        // mixed content on an https page -> pages render without CSS/JS.
        //
        // TRUSTED_PROXIES accepts a comma-separated list of IPs/CIDRs, or '*' to
        // trust every proxy. Default '*' keeps GitHub Codespaces (dynamic
        // forwarding IPs) working out of the box; narrow it in production, e.g.
        //   TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12
        $trustedProxies = env('TRUSTED_PROXIES', '*');
        $trustedProxies = $trustedProxies === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', (string) $trustedProxies))));

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
