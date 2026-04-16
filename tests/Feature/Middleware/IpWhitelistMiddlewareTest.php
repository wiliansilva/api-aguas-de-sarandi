<?php

namespace Tests\Feature\Middleware;

use App\Services\ClienteService;
use Tests\TestCase;

class IpWhitelistMiddlewareTest extends TestCase
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
    // Lista vazia → permite todos os IPs
    // -------------------------------------------------------------------------

    public function test_permite_qualquer_ip_quando_lista_vazia(): void
    {
        config(['security.allowed_ips' => []]);

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Lista preenchida
    // -------------------------------------------------------------------------

    public function test_permite_ip_na_whitelist(): void
    {
        config(['security.allowed_ips' => ['127.0.0.1']]);

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        // O IP padrão de testes do Laravel é 127.0.0.1
        $response->assertStatus(200);
    }

    public function test_bloqueia_ip_fora_da_whitelist(): void
    {
        config(['security.allowed_ips' => ['10.0.0.1', '192.168.1.100']]);

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        // 127.0.0.1 não está na lista → 403
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error'   => 'Acesso negado. IP não autorizado.',
            ]);
    }

    public function test_permite_multiplos_ips_na_whitelist(): void
    {
        config(['security.allowed_ips' => ['10.0.0.1', '127.0.0.1', '192.168.1.1']]);

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200);
    }

    public function test_resposta_403_nao_expoe_detalhes_sensiveis(): void
    {
        config(['security.allowed_ips' => ['10.0.0.1']]);

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(403);
        $json = $response->json();

        // Garante que apenas os campos esperados estejam presentes
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('error', $json);
        $this->assertArrayNotHasKey('ip', $json);
        $this->assertArrayNotHasKey('allowed_ips', $json);
    }

    public function test_403_inclui_headers_de_seguranca(): void
    {
        config(['security.allowed_ips' => ['10.0.0.1']]);

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        // SecurityHeadersMiddleware deve rodar antes do IpWhitelistMiddleware
        $response->assertStatus(403)
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
