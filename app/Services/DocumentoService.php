<?php

namespace App\Services;

use App\Repositories\DocumentoRepository;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class DocumentoService
{
    public function __construct(
        private readonly DocumentoRepository $documentoRepository
    ) {}

    /**
     * Retorna os documentos em aberto de uma ligação.
     *
     * @param  string  $ligacao
     * @return array{ligacao: string, documentos: array, total: int}
     */
    public function consultarDocumentosEmAberto(string $ligacao): array
    {
        $rows = $this->documentoRepository->findDocumentosEmAberto($ligacao);

        $documentos = array_map(function ($row) {
            $r = (array) $row;
            $qrCode = $r['qrCode'] ?? null;

            return [
                'referencia'    => $r['referencia'] ?? null,
                'vencimento'    => $r['vencimento'] ?? null,
                'valor'         => isset($r['valor']) && $r['valor'] !== null ? (float) $r['valor'] : null,
                'qrCode'        => $qrCode,
                'qrCodeImagem'  => $this->gerarQrCodeBase64($qrCode),
            ];
        }, $rows);

        return [
            'ligacao'    => $ligacao,
            'documentos' => $documentos,
            'total'      => count($documentos),
        ];
    }

    private function gerarQrCodeBase64(?string $conteudo): ?string
    {
        if (empty($conteudo)) {
            return null;
        }

        $writer = new PngWriter();
        $qrCode = new QrCode($conteudo);
        $result = $writer->write($qrCode);

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }
}