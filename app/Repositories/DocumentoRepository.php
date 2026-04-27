<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DocumentoRepository
{
    /**
     * Busca documentos em aberto (não pagos) de uma ligação.
     *
     * @param  string  $ligacao  Número da ligação (CADUSU.C01LIG).
     * @return array
     */
    public function findDocumentosEmAberto(string $ligacao): array
    {
        return DB::select(
                "SELECT
                    D.C03MES  AS referencia,
                    D.C03VEC  AS vencimento,
                    D.C03DEV  AS valor,
                    D.QRCODE  AS qrCode
                FROM CADDEV D
                INNER JOIN CADUSU C
                    ON C.C01LIG = D.C03NLG
                WHERE D.C03NLG  = ?
                  AND D.C03DPG IS NULL
                ORDER BY D.C03VEC ASC",
                [$ligacao]
            );
    }
}