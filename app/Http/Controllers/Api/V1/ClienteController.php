<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ConsultaClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Services\ClienteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClienteController
{
    use ApiResponse;

    public function __construct(
        private readonly ClienteService $clienteService
    ) {}

    public function consulta(ConsultaClienteRequest $request): JsonResponse
    {
        $clientes = $this->clienteService->consultarPorDocumento(
            $request->validated('documento')
        );

        $data = array_map(
            fn($cliente) => (new ClienteResource($cliente))->resolve($request),
            $clientes
        );

        return $this->success($data);
    }
}