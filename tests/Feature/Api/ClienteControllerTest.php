<?php

namespace Tests\Feature\Api;

use App\Services\ClienteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteControllerTest extends TestCase
{
    private string $validAuth;

    protected function setUp(): void
    {
        parent::setUp();

        $user           = config('auth.api_basic.username', 'integracao');
        $pass           = config('auth.api_basic.password', 'senhaSegura123');
        $this->validAuth = 'Basic ' . base64_encode("{$user}:{$pass}");
    }

    // -------------------------------------------------------------------------
    // Sucesso
    // -------------------------------------------------------------------------

    public function test_retorna_200_com_cpf_valido(): void
    {
        $this->mock(ClienteService::class, function ($mock) {
            $mock->shouldReceive('consultarPorDocumento')
                ->once()
                ->with('12345678901')
                ->andReturn([
                    [
                        'Ligacao'         => 1001,
                        'DV'              => '2',
                        'Nome'            => 'João da Silva',
                        'CPF_CNPJ'        => '12345678901',
                        'CPF_CNPJ_2'      => null,
                        'Rua'             => 'Rua das Flores',
                        'Bairro'          => 'Centro',
                        'Numero'          => '100',
                        'Complemento'     => null,
                        'nomeDoMunicipio' => 'Sarandi',
                    ],
                ]);
        });

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data'  => [['ligacao', 'dv', 'nome', 'cpf_cnpj', 'cpf_cnpj_2', 'rua', 'bairro', 'numero', 'complemento', 'municipio']],
                'total',
            ]);
    }

    public function test_retorna_200_com_cnpj_valido(): void
    {
        $this->mock(ClienteService::class, function ($mock) {
            $mock->shouldReceive('consultarPorDocumento')
                ->once()
                ->with('12345678000195')
                ->andReturn([]);
        });

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678000195',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'total' => 0]);
    }

    public function test_normaliza_cpf_com_formatacao(): void
    {
        $this->mock(ClienteService::class, function ($mock) {
            // A request deve remover pontos e traço antes de chamar o service
            $mock->shouldReceive('consultarPorDocumento')
                ->once()
                ->with('12345678901')
                ->andReturn([]);
        });

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=123.456.789-01',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200);
    }

    public function test_normaliza_cnpj_com_formatacao(): void
    {
        $this->mock(ClienteService::class, function ($mock) {
            $mock->shouldReceive('consultarPorDocumento')
                ->once()
                ->with('12345678000195')
                ->andReturn([]);
        });

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12.345.678/0001-95',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200);
    }

    public function test_retorna_lista_vazia_quando_cliente_nao_encontrado(): void
    {
        $this->mock(ClienteService::class, function ($mock) {
            $mock->shouldReceive('consultarPorDocumento')->andReturn([]);
        });

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [],
                'total'   => 0,
            ]);
    }

    public function test_total_reflete_quantidade_de_clientes_retornados(): void
    {
        $clientes = [
            ['Ligacao' => 1001, 'DV' => '1', 'Nome' => 'João', 'CPF_CNPJ' => '12345678901', 'CPF_CNPJ_2' => null, 'Rua' => null, 'Bairro' => null, 'Numero' => null, 'Complemento' => null, 'nomeDoMunicipio' => null],
            ['Ligacao' => 1002, 'DV' => '2', 'Nome' => 'Maria', 'CPF_CNPJ' => '12345678901', 'CPF_CNPJ_2' => null, 'Rua' => null, 'Bairro' => null, 'Numero' => null, 'Complemento' => null, 'nomeDoMunicipio' => null],
        ];

        $this->mock(ClienteService::class, function ($mock) use ($clientes) {
            $mock->shouldReceive('consultarPorDocumento')->andReturn($clientes);
        });

        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJsonPath('total', 2);
    }

    // -------------------------------------------------------------------------
    // Validação
    // -------------------------------------------------------------------------

    public function test_retorna_422_sem_parametro_documento(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'error', 'detail']);
    }

    public function test_retorna_422_com_documento_muito_curto(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=1234567890',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'error' => 'Parâmetros inválidos.']);
    }

    public function test_retorna_422_com_documento_de_12_digitos(): void
    {
        // 12 dígitos não é CPF (11) nem CNPJ (14)
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=123456789012',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422);
    }

    public function test_retorna_422_com_documento_com_letras(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=1234567890A',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Autenticação
    // -------------------------------------------------------------------------

    public function test_retorna_401_sem_header_authorization(): void
    {
        $response = $this->getJson('/api/v1/clientes/consulta?documento=12345678901');

        $response->assertStatus(401);
    }

    public function test_retorna_401_com_credenciais_invalidas(): void
    {
        $response = $this->getJson(
            '/api/v1/clientes/consulta?documento=12345678901',
            ['Authorization' => 'Basic ' . base64_encode('usuario:senhaErrada')]
        );

        $response->assertStatus(401);
    }
}
