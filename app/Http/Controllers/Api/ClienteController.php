<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    public function __construct(
        private readonly ClienteService $clienteService
    ) {}

    public function consulta(Request $request): JsonResponse
    {
        $documento = $request->query('documento');

        if (empty($documento)) {
            return response()->json([
                'error'   => 'Parâmetro obrigatório ausente.',
                'message' => 'O parâmetro "documento" (CPF ou CNPJ) é obrigatório.',
            ], 422);
        }

        // Remove qualquer formatação (pontos, traços, barras)
        $documento = preg_replace('/\D/', '', $documento);

        if (!in_array(strlen($documento), [11, 14])) {
            return response()->json([
                'error'   => 'Documento inválido.',
                'message' => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) sem formatação.',
            ], 422);
        }

        $resultado = $this->clienteService->consultarPorDocumento($documento);

        return response()->json([
            'data'  => $resultado,
            'total' => count($resultado),
        ], 200);
    }
}