<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = config('security.allowed_ips', []);

        // Se a lista estiver vazia, permite qualquer IP
        if (empty($allowedIps)) {
            return $next($request);
        }

        if (!in_array($request->ip(), $allowedIps, strict: true)) {
            return response()->json([
                'success' => false,
                'error'   => 'Acesso negado. IP não autorizado.',
            ], 403);
        }

        return $next($request);
    }
}