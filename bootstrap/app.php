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
        // Middlewares globais — aplicados em TODAS as requisições da API
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\IpWhitelistMiddleware::class,
            \App\Http\Middleware\RateLimitMiddleware::class,
            \App\Http\Middleware\AuditLogMiddleware::class,
        ]);

        // Aliases para uso nas rotas
        $middleware->alias([
            'auth.basic.custom' => \App\Http\Middleware\BasicAuthMiddleware::class,
            'security.headers'  => \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'ip.whitelist'      => \App\Http\Middleware\IpWhitelistMiddleware::class,
            'rate.limit'        => \App\Http\Middleware\RateLimitMiddleware::class,
            'audit.log'         => \App\Http\Middleware\AuditLogMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*')
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Parâmetros inválidos.',
                    'detail'  => $e->validator->errors()->first(),
                ], 422);
            }
        });

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