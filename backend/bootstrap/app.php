<?php

use App\Http\Middleware\LogRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(LogRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // This app is API-only (see ADR 0002) — every error should render as
        // JSON, even for a client that forgets to send "Accept:
        // application/json" (Laravel's default only renders JSON when the
        // request already declares it expects that).
        $exceptions->shouldRenderJsonWhen(fn ($request, $throwable) => $request->is('api/*'));
    })->create();
