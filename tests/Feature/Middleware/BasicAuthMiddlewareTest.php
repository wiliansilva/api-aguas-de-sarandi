<?php

namespace Tests\Feature\Middleware;

use App\Services\ClienteService;
use Tests\TestCase;

class BasicAuthMiddlewareTest extends TestCase
{
    private string $validUser;
    private string $validPass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validUser = config('auth.api_basic.username', 'integracao');
        $this->validPass = config('auth.api_basic.password', 'senhaSegura123');

        // Evita acesso ao banco de dados nos testes de middleware
        $this->mock(ClienteService::class, function ($mock) {
            $mock->shouldReceive('consultarPorDocumento')->andReturn([]);
        });
    }

    // -------------------------------------------------------------------------
    // Header ausente / malformado
    // -------------------------------------------------------------------------

    public function test_retorna_401_sem_header_authorization(): void
    {
        $response = $this->getJson('/api/v1/clientes/consulta?documento=12345678901');

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'error' => 'Não autorizado.'])
            ->assertHeader('WWW-Authenticate', 'Basic realm="API"');
    }

    public function test_retorna_401_com_header_vazio(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => '']
        );

        $response->assertStatus(401);
    }

    public function test_retorna_401_sem_prefixo_basic(): void
    {
        $credentials = base64_encode("{$this->validUser}:{$this->validPass}");

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => "Bearer {$credentials}"]
        );

        $response->assertStatus(401);
    }

    public function test_retorna_401_com_base64_invalido(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic credenciais_invalidas!!!']
        );

        $response->assertStatus(401);
    }

    public function test_retorna_401_sem_separador_dois_pontos(): void
    {
        // Base64 válido mas sem ":" separando usuário e senha
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode('usuariosemsenha')]
        );

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Credenciais incorretas
    // -------------------------------------------------------------------------

    public function test_retorna_401_com_usuario_errado(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode("usuario_errado:{$this->validPass}")]
        );

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_retorna_401_com_senha_errada(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode("{$this->validUser}:senha_errada")]
        );

        $response->assertStatus(401);
    }

    public function test_retorna_401_com_usuario_e_senha_errados(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode('errado:errado')]
        );

        $response->assertStatus(401);
    }

    public function test_retorna_401_com_credenciais_em_branco(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode(':')]
        );

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Credenciais corretas
    // -------------------------------------------------------------------------

    public function test_permite_acesso_com_credenciais_validas(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode("{$this->validUser}:{$this->validPass}")]
        );

        $response->assertStatus(200);
    }

    public function test_resposta_401_inclui_header_www_authenticate(): void
    {
        $response = $this->getJson('/api/v1/clientes/consulta?documento=12345678901');

        $response->assertHeader('WWW-Authenticate', 'Basic realm="API"');
    }

    public function test_senha_com_dois_pontos_e_tratada_corretamente(): void
    {
        // Garante que apenas o primeiro ":" separa usuário da senha
        config(['auth.api_basic.password' => 'senha:com:dois:pontos']);

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode("{$this->validUser}:senha:com:dois:pontos")]
        );

        $response->assertStatus(200);
    }
}
