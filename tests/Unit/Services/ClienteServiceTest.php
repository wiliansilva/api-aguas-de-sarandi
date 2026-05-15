<?php

namespace Tests\Unit\Services;

use App\Repositories\ClienteRepository;
use App\Services\ClienteService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClienteServiceTest extends TestCase
{
    private ClienteRepository|MockObject $repository;
    private ClienteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(ClienteRepository::class);
        $this->service    = new ClienteService($this->repository);
    }

    public function test_retorna_array_vazio_quando_nenhuma_linha_encontrada(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findByDocumento')
            ->with('12345678901')
            ->willReturn([]);

        $resultado = $this->service->consultarPorDocumento('12345678901');

        $this->assertSame([], $resultado);
    }

    public function test_mapeia_todos_os_campos_corretamente(): void
    {
        $row = (object) [
            'Ligacao'         => 1001,
            'DV'              => '2',
            'Nome'            => 'João da Silva',
            'CPF_CNPJ'        => '12345678901',
            'CPF_CNPJ_2'      => null,
            'nomeDaRua'       => 'Rua das Flores',
            'nomeDoBairro'    => 'Centro',
            'Numero'          => '123',
            'Complemento'     => 'Apto 1',
            'nomeDoMunicipio' => 'Sarandi',
        ];

        $this->repository->method('findByDocumento')->willReturn([$row]);

        $resultado = $this->service->consultarPorDocumento('12345678901');

        $this->assertCount(1, $resultado);
        $this->assertSame([
            'Ligacao'         => 1001,
            'DV'              => '2',
            'Nome'            => 'João da Silva',
            'CPF_CNPJ'        => '12345678901',
            'CPF_CNPJ_2'      => null,
            'Rua'             => 'Rua das Flores',
            'Bairro'          => 'Centro',
            'Numero'          => '123',
            'Complemento'     => 'Apto 1',
            'nomeDoMunicipio' => 'Sarandi',
        ], $resultado[0]);
    }

    public function test_renomeia_nomeDaRua_para_Rua(): void
    {
        $this->repository->method('findByDocumento')
            ->willReturn([(object) ['nomeDaRua' => 'Av. Brasil']]);

        $resultado = $this->service->consultarPorDocumento('12345678901');

        $this->assertArrayHasKey('Rua', $resultado[0]);
        $this->assertSame('Av. Brasil', $resultado[0]['Rua']);
        $this->assertArrayNotHasKey('nomeDaRua', $resultado[0]);
    }

    public function test_renomeia_nomeDoBairro_para_Bairro(): void
    {
        $this->repository->method('findByDocumento')
            ->willReturn([(object) ['nomeDoBairro' => 'Jardim América']]);

        $resultado = $this->service->consultarPorDocumento('12345678901');

        $this->assertArrayHasKey('Bairro', $resultado[0]);
        $this->assertSame('Jardim América', $resultado[0]['Bairro']);
        $this->assertArrayNotHasKey('nomeDoBairro', $resultado[0]);
    }

    public function test_campos_ausentes_retornam_null(): void
    {
        $this->repository->method('findByDocumento')
            ->willReturn([(object) []]);

        $resultado = $this->service->consultarPorDocumento('12345678901');

        $this->assertNull($resultado[0]['Ligacao']);
        $this->assertNull($resultado[0]['DV']);
        $this->assertNull($resultado[0]['Nome']);
        $this->assertNull($resultado[0]['CPF_CNPJ']);
        $this->assertNull($resultado[0]['CPF_CNPJ_2']);
        $this->assertNull($resultado[0]['Rua']);
        $this->assertNull($resultado[0]['Bairro']);
        $this->assertNull($resultado[0]['Numero']);
        $this->assertNull($resultado[0]['Complemento']);
        $this->assertNull($resultado[0]['nomeDoMunicipio']);
    }

    public function test_mapeia_multiplas_linhas(): void
    {
        $rows = [
            (object) ['Ligacao' => 1001, 'Nome' => 'João'],
            (object) ['Ligacao' => 1002, 'Nome' => 'Maria'],
            (object) ['Ligacao' => 1003, 'Nome' => 'Pedro'],
        ];

        $this->repository->method('findByDocumento')->willReturn($rows);

        $resultado = $this->service->consultarPorDocumento('12345678901');

        $this->assertCount(3, $resultado);
        $this->assertSame(1001, $resultado[0]['Ligacao']);
        $this->assertSame(1002, $resultado[1]['Ligacao']);
        $this->assertSame(1003, $resultado[2]['Ligacao']);
    }

    public function test_passa_documento_correto_ao_repositorio(): void
    {
        $documento = '12345678000195';

        $this->repository
            ->expects($this->once())
            ->method('findByDocumento')
            ->with($documento)
            ->willReturn([]);

        $this->service->consultarPorDocumento($documento);
    }

    public function test_converte_objeto_para_array_no_mapeamento(): void
    {
        $row = (object) ['Ligacao' => 999, 'Nome' => 'Teste'];

        $this->repository->method('findByDocumento')->willReturn([$row]);

        $resultado = $this->service->consultarPorDocumento('12345678901');

        $this->assertIsArray($resultado[0]);
        $this->assertSame(999, $resultado[0]['Ligacao']);
    }

    // -------------------------------------------------------------------------
    // consultarPorLigacao
    // -------------------------------------------------------------------------

    public function test_consultar_por_ligacao_retorna_array_vazio(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findByLigacao')
            ->with('161615')
            ->willReturn([]);

        $resultado = $this->service->consultarPorLigacao('161615');

        $this->assertSame([], $resultado);
    }

    public function test_consultar_por_ligacao_retorna_clientes_formatados(): void
    {
        $row = (object) [
            'Ligacao'         => '161615',
            'DV'              => '1',
            'Nome'            => 'Maria Souza',
            'CPF_CNPJ'        => '98765432100',
            'CPF_CNPJ_2'      => null,
            'nomeDaRua'       => 'Av. Central',
            'nomeDoBairro'    => 'Vila Nova',
            'Numero'          => '200',
            'Complemento'     => null,
            'nomeDoMunicipio' => 'Sarandi',
        ];

        $this->repository->method('findByLigacao')->willReturn([$row]);

        $resultado = $this->service->consultarPorLigacao('161615');

        $this->assertCount(1, $resultado);
        $this->assertSame([
            'Ligacao'         => '161615',
            'DV'              => '1',
            'Nome'            => 'Maria Souza',
            'CPF_CNPJ'        => '98765432100',
            'CPF_CNPJ_2'      => null,
            'Rua'             => 'Av. Central',
            'Bairro'          => 'Vila Nova',
            'Numero'          => '200',
            'Complemento'     => null,
            'nomeDoMunicipio' => 'Sarandi',
        ], $resultado[0]);
    }

    public function test_consultar_por_ligacao_passa_ligacao_correta_ao_repositorio(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findByLigacao')
            ->with('0161615')
            ->willReturn([]);

        $this->service->consultarPorLigacao('0161615');
    }
}
