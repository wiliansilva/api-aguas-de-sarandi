<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function __construct(
        private readonly RateLimiter $limiter
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Chave única por IP + rota
        $key = 'api:' . $request->ip() . ':' . $request->path();

        // Máximo de 60 requisições por minuto por IP
        $maxAttempts = (int) config('security.rate_limit.max_attempts', 60);
        $decaySeconds = (int) config('security.rate_limit.decay_seconds', 60);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'success' => false,
                'error'   => 'Muitas requisições. Tente novamente em alguns instantes.',
                'retry_after_seconds' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After'               => $retryAfter,
                'X-RateLimit-Limit'         => $maxAttempts,
                'X-RateLimit-Remaining'     => 0,
            ]);
        }

        $this->limiter->hit($key, $decaySeconds);

        $remaining = $maxAttempts - $this->limiter->attempts($key);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit',     $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));

        return $response;
    }
}