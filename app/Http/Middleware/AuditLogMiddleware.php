<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Registra cada acesso à API com dados relevantes para auditoria
        Log::channel('audit')->info('API Access', [
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'url'         => $request->fullUrl(),
            'user_agent'  => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'timestamp'   => now()->toIso8601String(),
        ]);

        return $response;
    }
}