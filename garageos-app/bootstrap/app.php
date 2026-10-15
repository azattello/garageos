<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'trial' => App\Http\Middleware\EnsureActiveSubscription::class,
            'feature' => App\Http\Middleware\EnsureSubscriptionFeature::class,
            'platform-admin' => App\Http\Middleware\EnsurePlatformAdmin::class,
        ]);
        $middleware->web(append: [
            App\Http\Middleware\EnsureActiveAccount::class,
            App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
