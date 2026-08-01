<?php

use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Allow the Vue SPA to authenticate against the API using session cookies
        // instead of bearer tokens. See config/sanctum.php for the stateful domains.
        $middleware->statefulApi();

        $middleware->alias([
            'permission' => EnsureUserHasPermission::class,
        ]);

        // Appended so it runs after the session and auth middleware: the signed-in
        // user's saved preference is the whole point, and it is not resolvable
        // any earlier in the stack.
        $middleware->api(append: [SetLocale::class]);
        $middleware->web(append: [SetLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
