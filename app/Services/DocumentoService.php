<?php

namespace App\Services;

use App\Repositories\DocumentoRepository;

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

            return [
                'referencia' => $r['referencia'] ?? null,
                'vencimento' => $r['vencimento'] ?? null,
                'valor'      => isset($r['valor']) && $r['valor'] !== null ? (float) $r['valor'] : null,
                'qrCode'     => $r['qrCode'] ?? null,
            ];
        }, $rows);

        return [
            'ligacao'    => $ligacao,
            'documentos' => $documentos,
            'total'      => count($documentos),
        ];
    }
}