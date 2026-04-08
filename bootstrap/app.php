<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registra o alias para uso nas rotas
        $middleware->alias([
            'auth.basic.custom' => \App\Http\Middleware\BasicAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Garante que exceções na API retornem JSON
        $exceptions->shouldRenderJsonWhen(
            fn($request) => $request->is('api/*')
        );
    })
    ->create();