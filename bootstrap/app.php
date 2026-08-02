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
        // ponytail: trusts all proxies. Safe while the app is only reachable through
        // a proxy. If the origin is ever exposed directly to the internet, narrow
        // `at` to the proxy CIDR (e.g. env-driven TRUSTED_PROXIES).
        $middleware->trustProxies(
            at: '*',
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
