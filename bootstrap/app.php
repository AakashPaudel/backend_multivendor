<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $defaultApiErrorMessage = static fn (int $statusCode): string => match ($statusCode) {
            404 => 'Resource not found.',
            405 => 'Method not allowed.',
            429 => 'Too many attempts.',
            default => 'Request failed.',
        };

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/*')
                || $request->expectsJson()
                || $e instanceof HttpExceptionInterface
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'Unauthenticated.',
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'This action is unauthorized.',
                ], 403);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], $exception->status);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($defaultApiErrorMessage) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = $exception->getStatusCode();

                return response()->json([
                    'message' => $statusCode === 404
                        ? $defaultApiErrorMessage($statusCode)
                        : ($exception->getMessage() ?: $defaultApiErrorMessage($statusCode)),
                ], $statusCode, $exception->getHeaders());
            }
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => app()->hasDebugModeEnabled()
                        ? $exception->getMessage()
                        : 'Server error.',
                ], 500);
            }
        });
    })->create();
