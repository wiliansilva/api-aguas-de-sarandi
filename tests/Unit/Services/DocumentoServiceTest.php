<?php

namespace Tests\Unit\Services;

use App\Repositories\DocumentoRepository;
use App\Services\DocumentoService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DocumentoServiceTest extends TestCase
{
    private DocumentoRepository|MockObject $repository;
    private DocumentoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(DocumentoRepository::class);
        $this->service    = new DocumentoService($this->repository);
    }

    public function test_retorna_estrutura_correta(): void
    {
        $this->repository->method('findDocumentosEmAberto')->willReturn([]);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);

        $this->assertArrayHasKey('ligacao', $resultado);
        $this->assertArrayHasKey('documentos', $resultado);
        $this->assertArrayHasKey('total', $resultado);
    }

    public function test_ligacao_no_resultado_corresponde_ao_parametro(): void
    {
        $this->repository->method('findDocumentosEmAberto')->willReturn([]);

        $resultado = $this->service->consultarDocumentosEmAberto(9999);

        $this->assertSame(9999, $resultado['ligacao']);
    }

    public function test_total_reflete_quantidade_de_documentos(): void
    {
        $rows = [
            (object) ['referencia' => '2024-01', 'vencimento' => '2024-01-10', 'valor' => '150.50', 'qrCode' => null],
            (object) ['referencia' => '2024-02', 'vencimento' => '2024-02-10', 'valor' => '200.00', 'qrCode' => null],
        ];

        $this->repository->method('findDocumentosEmAberto')->willReturn($rows);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);

        $this->assertSame(2, $resultado['total']);
        $this->assertCount(2, $resultado['documentos']);
    }

    public function test_valor_e_convertido_para_float(): void
    {
        $row = (object) [
            'referencia' => '2024-01',
            'vencimento' => '2024-01-10',
            'valor'      => '150.50',
            'qrCode'     => null,
        ];

        $this->repository->method('findDocumentosEmAberto')->willReturn([$row]);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);

        $this->assertIsFloat($resultado['documentos'][0]['valor']);
        $this->assertSame(150.50, $resultado['documentos'][0]['valor']);
    }

    public function test_valor_null_permanece_null(): void
    {
        $row = (object) [
            'referencia' => '2024-01',
            'vencimento' => '2024-01-10',
            'valor'      => null,
            'qrCode'     => null,
        ];

        $this->repository->method('findDocumentosEmAberto')->willReturn([$row]);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);

        $this->assertNull($resultado['documentos'][0]['valor']);
    }

    public function test_documentos_vazios_retornam_total_zero(): void
    {
        $this->repository->method('findDocumentosEmAberto')->willReturn([]);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);

        $this->assertSame(0, $resultado['total']);
        $this->assertSame([], $resultado['documentos']);
    }

    public function test_mapeia_campos_do_documento_corretamente(): void
    {
        $row = (object) [
            'referencia' => '2024-03',
            'vencimento' => '2024-03-15',
            'valor'      => '99.99',
            'qrCode'     => 'QR_CODE_DATA',
        ];

        $this->repository->method('findDocumentosEmAberto')->willReturn([$row]);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);
        $doc       = $resultado['documentos'][0];

        $this->assertSame('2024-03', $doc['referencia']);
        $this->assertSame('2024-03-15', $doc['vencimento']);
        $this->assertSame(99.99, $doc['valor']);
        $this->assertSame('QR_CODE_DATA', $doc['qrCode']);
    }

    public function test_campos_ausentes_retornam_null(): void
    {
        $this->repository->method('findDocumentosEmAberto')
            ->willReturn([(object) []]);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);
        $doc       = $resultado['documentos'][0];

        $this->assertNull($doc['referencia']);
        $this->assertNull($doc['vencimento']);
        $this->assertNull($doc['valor']);
        $this->assertNull($doc['qrCode']);
    }

    public function test_passa_ligacao_correta_ao_repositorio(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findDocumentosEmAberto')
            ->with(5678)
            ->willReturn([]);

        $this->service->consultarDocumentosEmAberto(5678);
    }

    public function test_valor_inteiro_convertido_para_float(): void
    {
        $row = (object) ['referencia' => null, 'vencimento' => null, 'valor' => '300', 'qrCode' => null];

        $this->repository->method('findDocumentosEmAberto')->willReturn([$row]);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);

        $this->assertIsFloat($resultado['documentos'][0]['valor']);
        $this->assertSame(300.0, $resultado['documentos'][0]['valor']);
    }

    public function test_mapeia_multiplos_documentos_preservando_ordem(): void
    {
        $rows = [
            (object) ['referencia' => '2024-01', 'vencimento' => '2024-01-10', 'valor' => '100.00', 'qrCode' => null],
            (object) ['referencia' => '2024-02', 'vencimento' => '2024-02-10', 'valor' => '200.00', 'qrCode' => null],
            (object) ['referencia' => '2024-03', 'vencimento' => '2024-03-10', 'valor' => '300.00', 'qrCode' => null],
        ];

        $this->repository->method('findDocumentosEmAberto')->willReturn($rows);

        $resultado = $this->service->consultarDocumentosEmAberto(1001);

        $this->assertSame('2024-01', $resultado['documentos'][0]['referencia']);
        $this->assertSame('2024-02', $resultado['documentos'][1]['referencia']);
        $this->assertSame('2024-03', $resultado['documentos'][2]['referencia']);
        $this->assertSame(3, $resultado['total']);
    }
}
