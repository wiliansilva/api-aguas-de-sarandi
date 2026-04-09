<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.basic.custom' => \App\Http\Middleware\BasicAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Todas as exceções da API retornam JSON
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*')
        );

        // Erros de validação padronizados
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Parâmetros inválidos.',
                    'detail'  => $e->validator->errors()->first(),
                ], 422);
            }
        });

        // Qualquer erro inesperado
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Erro interno no servidor.',
                    'detail'  => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        });
    })
    ->create();