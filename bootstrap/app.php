<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // ── Sanctum Middleware ──
        $middleware->statefulApi();

        // ── Step 2a: Append SystemAuditMiddleware to the API middleware group ──
        // Runs automatically on every API request that is non-GET.
        $middleware->appendToGroup('api', [
            \App\Modules\LoggingAudit\Middlewares\SystemAuditMiddleware::class,
        ]);

        // ── Step 2c: Register named middleware aliases ──
        // Allows controllers/routes to use short names instead of full class paths.
        $middleware->alias([
            'role' => \App\Modules\AuthIdentity\Middlewares\CheckRoleMiddleware::class,
            'driver.active' => \App\Modules\AuthIdentity\Middlewares\CheckDriverActiveMiddleware::class,
        ]);

        $middleware->api(append: [
            \App\Modules\LoggingAudit\Middlewares\SystemAuditMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. يرجى التأكد من إرسال التوكن الصحيح.',
                ], 401);
            }
        });
        $exceptions->render(function (\Symfony\Component\Routing\Exception\RouteNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($e->getMessage() === 'Route [login] not defined.' && $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. يرجى إرسال التوكن الصحيح في الـ Headers.',
                ], 401);
            }
        });
    })->create();

