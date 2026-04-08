<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ClienteService
{
    /**
     * Consulta clientes pelo CPF ou CNPJ informado.
     *
     * @param  string  $documento  CPF (11 dígitos) ou CNPJ (14 dígitos), somente números.
     * @return array
     */
    public function consultarPorDocumento(string $documento): array
    {
        $rows = DB::connection('mysql')
            ->select(
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

        // MySQL retorna os aliases exatamente como definidos no SELECT
        return array_map(function ($row) {
            $r = (array) $row;
            return [
                'Ligacao'         => $r['Ligacao']         ?? null,
                'DV'              => $r['DV']              ?? null,
                'Nome'            => $r['Nome']            ?? null,
                'CPF_CNPJ'        => $r['CPF_CNPJ']        ?? null,
                'CPF_CNPJ_2'      => $r['CPF_CNPJ_2']      ?? null,
                'Rua'             => $r['nomeDaRua']        ?? null,
                'Bairro'          => $r['nomeDoBairro']     ?? null,
                'Numero'          => $r['Numero']           ?? null,
                'Complemento'     => $r['Complemento']      ?? null,
                'nomeDoMunicipio' => $r['nomeDoMunicipio']  ?? null,
            ];
        }, $rows);
    }
}