<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class LinhaDigitavelRepository
{
    /**
     * Busca o documento no banco para confirmar sua existência
     * e retornar os dados originais (C03DEV, C03NLG, C03MES, C03VEC).
     *
     * @param  int     $ligacao
     * @param  string  $referencia  Formato: MMAA (ex: 0326)
     * @param  string  $vencimento  Formato: YYYY-MM-DD
     * @param  float   $valor
     * @return object|null
     */
    public function findDocumento(int $ligacao, string $referencia, string $vencimento, float $valor): ?object
    {
        // C03MES é armazenado como MM/AA (ex: 03/26), então reconstituímos
        $mesBanco = substr($referencia, 0, 2) . '/' . substr($referencia, 2, 2);

        $resultado = DB::select(
                "SELECT
                    D.C03NLG AS ligacao,
                    D.C03MES AS referencia,
                    D.C03VEC AS vencimento,
                    D.C03DEV AS valor
                FROM CADDEV D
                WHERE D.C03NLG = ?
                  AND D.C03MES = ?
                  AND D.C03VEC = ?
                  AND D.C03DEV = ?
                  AND D.C03DPG IS NULL
                LIMIT 1",
                [$ligacao, $mesBanco, $vencimento, $valor]
            );

        return $resultado[0] ?? null;
    }
}