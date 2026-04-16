<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuthMiddleware
{
    /**
     * Valida o header Authorization com Basic Auth.
     * As credenciais são lidas das variáveis de ambiente:
     *   API_BASIC_USER e API_BASIC_PASSWORD
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Basic ')) {
            return $this->unauthorized('Header Authorization ausente ou inválido.');
        }

        $base64Credentials = substr($authHeader, 6);
        $decoded = base64_decode($base64Credentials, strict: true);

        if ($decoded === false || !str_contains($decoded, ':')) {
            return $this->unauthorized('Credenciais mal formatadas.');
        }

        [$username, $password] = explode(':', $decoded, 2);

        $validUser     = config('auth.api_basic.username');
        $validPassword = config('auth.api_basic.password');

        // hash_equals previne timing attacks:
        // compara as strings em tempo constante, impedindo que um atacante
        // descubra a senha medindo o tempo de resposta caractere por caractere.
        $userMatch     = hash_equals($validUser, $username);
        $passwordMatch = hash_equals($validPassword, $password);

        if (!$userMatch || !$passwordMatch) {
            return $this->unauthorized('Usuário ou senha incorretos.');
        }

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error'   => 'Não autorizado.',
            'message' => $message,
        ], 401)->withHeaders([
            'WWW-Authenticate' => 'Basic realm="API"',
        ]);
    }
}