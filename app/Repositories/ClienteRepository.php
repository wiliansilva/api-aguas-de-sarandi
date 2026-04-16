<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ClienteRepository
{
    /**
     * Busca clientes pelo CPF ou CNPJ, cobrindo ambos os campos (C01CGC e C01CGC2).
     *
     * @param  string  $documento  Somente dígitos (11 ou 14 caracteres).
     * @return array<int, array<string, mixed>>
     */
    public function findByDocumento(string $documento): array
    {
        return DB::select(
                "SELECT
                    C.C01LIG  AS Ligacao,
                    C.C01DV   AS DV,
                    C.C01NOM  AS Nome,
                    C.C01CGC  AS CPF_CNPJ,
                    C.C01CGC2 AS CPF_CNPJ_2,
                    R.T04NOM  AS nomeDaRua,
                    B.T03NOM  AS nomeDoBairro,
                    C.C01NUM  AS Numero,
                    C.C01BBS  AS Complemento,
                    M.T09NOM  AS nomeDoMunicipio
                FROM CADUSU C
                LEFT JOIN TABMUN M
                    ON M.T09COD = C.C01MUN
                LEFT JOIN TABRUA R
                    ON R.T04MUN = C.C01MUN
                   AND R.T04RUA = C.C01RUA
                   AND R.T04BAI = C.C01BAI
                LEFT JOIN TABBAI B
                    ON B.T03MUN = C.C01MUN
                   AND B.T03BAI = C.C01BAI
                WHERE ? IN (C.C01CGC, C.C01CGC2)",
                [$documento]
            );
    }
}