<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ConsultaDocumentosRequest;
use App\Http\Resources\DocumentoResource;
use App\Services\DocumentoService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DocumentoController
{
    use ApiResponse;

    public function __construct(
        private readonly DocumentoService $documentoService
    ) {}

    public function index(ConsultaDocumentosRequest $request): JsonResponse
    {
        $ligacao   = (int) $request->validated('ligacao');
        $resultado = $this->documentoService->consultarDocumentosEmAberto($ligacao);

        $documentos = array_map(
            fn($doc) => (new DocumentoResource($doc))->resolve($request),
            $resultado['documentos']
        );

        return response()->json([
            'success'    => true,
            'ligacao'    => $resultado['ligacao'],
            'documentos' => $documentos,
            'total'      => $resultado['total'],
        ]);
    }
}