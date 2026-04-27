<?php

namespace Tests\Feature\Api;

use App\Services\DocumentoService;
use Tests\TestCase;

class DocumentoControllerTest extends TestCase
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

    public function test_retorna_200_com_ligacao_valida(): void
    {
        $this->mock(DocumentoService::class, function ($mock) {
            $mock->shouldReceive('consultarDocumentosEmAberto')
                ->once()
                ->with('1001')
                ->andReturn([
                    'ligacao'    => '1001',
                    'documentos' => [
                        [
                            'referencia'   => '2024-01',
                            'vencimento'   => '2024-01-10',
                            'valor'        => 150.50,
                            'qrCode'       => null,
                            'qrCodeImagem' => null,
                        ],
                    ],
                    'total' => 1,
                ]);
        });

        $response = $this->getJson(
            '/api/v1/documentos/1001',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'ligacao' => '1001', 'total' => 1])
            ->assertJsonStructure([
                'success',
                'ligacao',
                'documentos' => [['referencia', 'vencimento', 'valor', 'qrCode', 'qrCodeImagem']],
                'total',
            ]);
    }

    public function test_retorna_documentos_vazios_quando_sem_debitos(): void
    {
        $this->mock(DocumentoService::class, function ($mock) {
            $mock->shouldReceive('consultarDocumentosEmAberto')
                ->once()
                ->with('5000')
                ->andReturn([
                    'ligacao'    => '5000',
                    'documentos' => [],
                    'total'      => 0,
                ]);
        });

        $response = $this->getJson(
            '/api/v1/documentos/5000',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success'    => true,
                'ligacao'    => '5000',
                'documentos' => [],
                'total'      => 0,
            ]);
    }

    public function test_total_reflete_quantidade_de_documentos(): void
    {
        $this->mock(DocumentoService::class, function ($mock) {
            $mock->shouldReceive('consultarDocumentosEmAberto')
                ->andReturn([
                    'ligacao'    => '1001',
                    'documentos' => [
                        ['referencia' => '2024-01', 'vencimento' => '2024-01-10', 'valor' => 100.0, 'qrCode' => null, 'qrCodeImagem' => null],
                        ['referencia' => '2024-02', 'vencimento' => '2024-02-10', 'valor' => 200.0, 'qrCode' => null, 'qrCodeImagem' => null],
                        ['referencia' => '2024-03', 'vencimento' => '2024-03-10', 'valor' => 300.0, 'qrCode' => null, 'qrCodeImagem' => null],
                    ],
                    'total' => 3,
                ]);
        });

        $response = $this->getJson(
            '/api/v1/documentos/1001',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'documentos');
    }

    public function test_estrutura_da_resposta_inclui_campos_obrigatorios(): void
    {
        $this->mock(DocumentoService::class, function ($mock) {
            $mock->shouldReceive('consultarDocumentosEmAberto')
                ->andReturn([
                    'ligacao'    => '1001',
                    'documentos' => [],
                    'total'      => 0,
                ]);
        });

        $response = $this->getJson(
            '/api/v1/documentos/1001',
            ['Authorization' => $this->validAuth]
        );

        $response->assertJsonStructure(['success', 'ligacao', 'documentos', 'total']);
    }

    public function test_qr_code_imagem_presente_na_resposta_quando_qrcode_preenchido(): void
    {
        $base64 = 'data:image/png;base64,' . base64_encode('fake-png-content');

        $this->mock(DocumentoService::class, function ($mock) use ($base64) {
            $mock->shouldReceive('consultarDocumentosEmAberto')
                ->andReturn([
                    'ligacao'    => '1001',
                    'documentos' => [
                        [
                            'referencia'   => '2024-01',
                            'vencimento'   => '2024-01-10',
                            'valor'        => 150.50,
                            'qrCode'       => '00020126580014br.gov.bcb.pix',
                            'qrCodeImagem' => $base64,
                        ],
                    ],
                    'total' => 1,
                ]);
        });

        $response = $this->getJson(
            '/api/v1/documentos/1001',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'documentos' => [['referencia', 'vencimento', 'valor', 'qrCode', 'qrCodeImagem']],
            ]);

        $imagem = $response->json('documentos.0.qrCodeImagem');
        $this->assertStringStartsWith('data:image/png;base64,', $imagem);
    }

    public function test_qr_code_imagem_null_quando_qrcode_ausente(): void
    {
        $this->mock(DocumentoService::class, function ($mock) {
            $mock->shouldReceive('consultarDocumentosEmAberto')
                ->andReturn([
                    'ligacao'    => '1001',
                    'documentos' => [
                        [
                            'referencia'   => '2024-01',
                            'vencimento'   => '2024-01-10',
                            'valor'        => 150.50,
                            'qrCode'       => null,
                            'qrCodeImagem' => null,
                        ],
                    ],
                    'total' => 1,
                ]);
        });

        $response = $this->getJson(
            '/api/v1/documentos/1001',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJsonPath('documentos.0.qrCodeImagem', null);
    }

    // -------------------------------------------------------------------------
    // Validação
    // -------------------------------------------------------------------------

    public function test_retorna_422_com_ligacao_zero(): void
    {
        $response = $this->getJson(
            '/api/v1/documentos/0',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'error' => 'Parâmetros inválidos.'])
            ->assertJsonStructure(['success', 'error', 'detail']);
    }

    public function test_retorna_422_com_ligacao_apenas_zeros(): void
    {
        $response = $this->getJson(
            '/api/v1/documentos/0000',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_retorna_422_com_ligacao_negativa(): void
    {
        $response = $this->getJson(
            '/api/v1/documentos/-1',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_retorna_422_com_ligacao_textual(): void
    {
        $response = $this->getJson(
            '/api/v1/documentos/abc',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_aceita_ligacao_com_zeros_a_esquerda(): void
    {
        $this->mock(DocumentoService::class, function ($mock) {
            $mock->shouldReceive('consultarDocumentosEmAberto')
                ->once()
                ->with('0001')
                ->andReturn([
                    'ligacao'    => '0001',
                    'documentos' => [],
                    'total'      => 0,
                ]);
        });

        $response = $this->getJson(
            '/api/v1/documentos/0001',
            ['Authorization' => $this->validAuth]
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'ligacao' => '0001']);
    }

    // -------------------------------------------------------------------------
    // Autenticação
    // -------------------------------------------------------------------------

    public function test_retorna_401_sem_header_authorization(): void
    {
        $response = $this->getJson('/api/v1/documentos/1001');

        $response->assertStatus(401);
    }

    public function test_retorna_401_com_credenciais_invalidas(): void
    {
        $response = $this->getJson(
            '/api/v1/documentos/1001',
            ['Authorization' => 'Basic ' . base64_encode('errado:errado')]
        );

        $response->assertStatus(401);
    }
}
