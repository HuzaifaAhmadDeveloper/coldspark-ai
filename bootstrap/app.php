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
        // app/Http/Middleware/VerifyCsrfToken.php's $except list is never
        // consulted unless registered here — Laravel 11+ doesn't auto-wire
        // a custom VerifyCsrfToken subclass by convention the way older
        // versions did. These routes are hit directly by external services
        // (Stripe, ESP webhooks, mailbox providers' one-click unsubscribe)
        // that can't send a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'webhooks/campaign-events',
            'unsubscribe/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
