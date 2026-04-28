<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\LinhaDigitavelRequest;
use App\Http\Resources\LinhaDigitavelResource;
use App\Services\LinhaDigitavelService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class LinhaDigitavelController
{
    use ApiResponse;

    public function __construct(
        private readonly LinhaDigitavelService $linhaDigitavelService
    ) {}

    public function gerar(LinhaDigitavelRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $resultado = $this->linhaDigitavelService->gerar(
            ligacao:    $dados['ligacao'],
            referencia: (string) $dados['referencia'],
            vencimento: (string) $dados['vencimento'],
            valor:      (float)  $dados['valor'],
        );

        return response()->json([
            'success' => true,
            ...(new LinhaDigitavelResource($resultado))->resolve($request),
        ]);
    }
}