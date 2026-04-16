<?php

namespace Tests\Feature\Middleware;

use App\Services\ClienteService;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private string $validAuth;

    protected function setUp(): void
    {
        parent::setUp();

        $user           = config('auth.api_basic.username', 'integracao');
        $pass           = config('auth.api_basic.password', 'senhaSegura123');
        $this->validAuth = 'Basic ' . base64_encode("{$user}:{$pass}");

        $this->mock(ClienteService::class, function ($mock) {
            $mock->shouldReceive('consultarPorDocumento')->andReturn([]);
        });
    }

    // -------------------------------------------------------------------------
    // Headers de segurança presentes em respostas autenticadas
    // -------------------------------------------------------------------------

    public function test_adiciona_x_content_type_options(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_adiciona_x_frame_options(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_adiciona_x_xss_protection(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_adiciona_referrer_policy(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_adiciona_permissions_policy(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    public function test_adiciona_content_security_policy(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertHeader('Content-Security-Policy', "default-src 'none'");
    }

    public function test_adiciona_strict_transport_security(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    // -------------------------------------------------------------------------
    // Headers de segurança presentes mesmo em respostas de erro (401)
    // -------------------------------------------------------------------------

    public function test_headers_presentes_em_resposta_401(): void
    {
        // O SecurityHeadersMiddleware roda antes do BasicAuthMiddleware,
        // portanto os headers devem aparecer mesmo em respostas de autenticação.
        $response = $this->getJson('/api/v1/clientes/consulta?documento=12345678901');

        $response->assertStatus(401);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_headers_presentes_em_resposta_422(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    // -------------------------------------------------------------------------
    // Headers sensíveis removidos
    // -------------------------------------------------------------------------

    public function test_remove_header_x_powered_by(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_remove_header_server(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $this->assertNull($response->headers->get('Server'));
    }
}
