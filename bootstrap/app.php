<?php

use App\Http\Middleware\RequireRole;
use Illuminate\Auth\AuthenticationException;
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

        $middleware->alias([
            'role' => RequireRole::class,
        ]);

        /*
         * API requests must never redirect to a web login route.
         */
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(
            function (
                AuthenticationException $e,
                Request $request
            ) {
                if ($request->is('api/*')) {
                    return response()->json([
                        'error' => [
                            'code' => 'UNAUTHENTICATED',
                            'message' => 'Authentication is required.',
                            'details' => [],
                        ],
                    ], 401);
                }

                return null;
            }
        );
    })
    ->create();