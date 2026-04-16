<?php

namespace Tests\Feature\Middleware;

use App\Services\ClienteService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    private string $validAuth;
    private string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $user           = config('auth.api_basic.username', 'integracao');
        $pass           = config('auth.api_basic.password', 'senhaSegura123');
        $this->validAuth = 'Basic ' . base64_encode("{$user}:{$pass}");
        $this->endpoint  = '/api/v1/clientes/consulta?documento=12345678901';

        $this->mock(ClienteService::class, function ($mock) {
            $mock->shouldReceive('consultarPorDocumento')->andReturn([]);
        });

        // Limpa o cache de rate limit antes de cada teste
        Cache::flush();
    }

    // -------------------------------------------------------------------------
    // Headers de rate limit
    // -------------------------------------------------------------------------

    public function test_resposta_inclui_header_x_ratelimit_limit(): void
    {
        $response = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
    }

    public function test_resposta_inclui_header_x_ratelimit_remaining(): void
    {
        $response = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_x_ratelimit_remaining_diminui_a_cada_requisicao(): void
    {
        $first = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $second = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $remainingFirst  = (int) $first->headers->get('X-RateLimit-Remaining');
        $remainingSecond = (int) $second->headers->get('X-RateLimit-Remaining');

        $this->assertGreaterThan($remainingSecond, $remainingFirst);
    }

    public function test_x_ratelimit_limit_reflete_configuracao(): void
    {
        config(['security.rate_limit.max_attempts' => 30]);

        $response = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200);
        $this->assertSame('30', $response->headers->get('X-RateLimit-Limit'));
    }

    // -------------------------------------------------------------------------
    // Limite excedido → 429
    // -------------------------------------------------------------------------

    public function test_retorna_429_apos_exceder_limite(): void
    {
        // Configura um limite muito baixo para o teste
        config([
            'security.rate_limit.max_attempts'  => 2,
            'security.rate_limit.decay_seconds' => 60,
        ]);

        Cache::flush();

        // Primeira requisição — dentro do limite
        $this->getJson($this->endpoint, ['Authorization' => $this->validAuth])
            ->assertStatus(200);

        // Segunda requisição — dentro do limite
        $this->getJson($this->endpoint, ['Authorization' => $this->validAuth])
            ->assertStatus(200);

        // Terceira requisição — excede o limite
        $response = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(429)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'error', 'retry_after_seconds']);
    }

    public function test_resposta_429_inclui_header_retry_after(): void
    {
        config([
            'security.rate_limit.max_attempts'  => 1,
            'security.rate_limit.decay_seconds' => 60,
        ]);

        Cache::flush();

        $this->getJson($this->endpoint, ['Authorization' => $this->validAuth]);

        $response = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(429);
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_resposta_429_inclui_headers_de_rate_limit(): void
    {
        config([
            'security.rate_limit.max_attempts'  => 1,
            'security.rate_limit.decay_seconds' => 60,
        ]);

        Cache::flush();

        $this->getJson($this->endpoint, ['Authorization' => $this->validAuth]);

        $response = $this->getJson(
            $this->endpoint,
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(429);
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    // -------------------------------------------------------------------------
    // Isolamento por rota
    // -------------------------------------------------------------------------

    public function test_limite_e_isolado_por_rota(): void
    {
        config([
            'security.rate_limit.max_attempts'  => 1,
            'security.rate_limit.decay_seconds' => 60,
        ]);

        Cache::flush();

        $this->mock(\App\Services\DocumentoService::class, function ($mock) {
            $mock->shouldReceive('consultarDocumentosEmAberto')->andReturn([
                'ligacao'    => 1001,
                'documentos' => [],
                'total'      => 0,
            ]);
        });

        // Esgota o limite na rota de clientes
        $this->getJson($this->endpoint, ['Authorization' => $this->validAuth])
            ->assertStatus(200);

        $this->getJson($this->endpoint, ['Authorization' => $this->validAuth])
            ->assertStatus(429);

        // A rota de documentos deve ter seu próprio limite independente
        $this->getJson(
            '/api/v1/documentos/1001',
            ['Authorization' => $this->validAuth]
        )->assertStatus(200);
    }
}
