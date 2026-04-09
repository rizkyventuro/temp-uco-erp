<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response) {
            if (! request()->expectsJson()) {
                if ($response->getStatusCode() === 404) {
                    return \Inertia\Inertia::render('errors/Error404')
                        ->toResponse(request())
                        ->setStatusCode(404);
                }
                if ($response->getStatusCode() === 403) {
                    return \Inertia\Inertia::render('errors/Error403')
                        ->toResponse(request())
                        ->setStatusCode(403);
                }
                // if ($response->getStatusCode() === 500) {
                //     return \Inertia\Inertia::render('errors/Error500')
                //         ->toResponse(request())
                //         ->setStatusCode(500);
                // }
                // if ($response->getStatusCode() === 500) {
                //     \Illuminate\Support\Facades\Log::error('500 Internal Server Error', [
                //         'url'     => request()->fullUrl(),
                //         'method'  => request()->method(),
                //         'ip'      => request()->ip(),
                //         'user_id' => request()->user()?->id,
                //         'message' => $response instanceof \Symfony\Component\HttpFoundation\Response
                //             ? $response->getContent()
                //             : 'No message available',
                //     ]);

                //     return \Inertia\Inertia::render('errors/Error500')
                //         ->toResponse(request())
                //         ->setStatusCode(500);
                // }
            }

            return $response;
        });
    })->create();
