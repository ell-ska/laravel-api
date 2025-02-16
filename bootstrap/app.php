<?php

use App\Exceptions\AuthException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        });

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => 'validation failed',
                'errors' => $e->errors(),
            ], 400);
        });

        $exceptions->render(function (Exception $e) {
            return response()->json([
                'message' => 'something went horribly wrong',
            ], 500);
        });
    })->create();
