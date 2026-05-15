<?php

namespace App\Services;

use App\Repositories\ClienteRepository;

class ClienteService
{
    public function __construct(
        private readonly ClienteRepository $clienteRepository
    ) {}

    /**
     * Consulta e formata os clientes pelo CPF ou CNPJ.
     *
     * @param  string  $documento  Somente dígitos (11 ou 14 caracteres).
     * @return array<int, array<string, mixed>>
     */
    public function consultarPorDocumento(string $documento): array
    {
        $rows = $this->clienteRepository->findByDocumento($documento);

        return array_map(function ($row) {
            $r = (array) $row;
            return [
                'Ligacao'         => $r['Ligacao']        ?? null,
                'DV'              => $r['DV']             ?? null,
                'Nome'            => $r['Nome']           ?? null,
                'CPF_CNPJ'        => $r['CPF_CNPJ']       ?? null,
                'CPF_CNPJ_2'      => $r['CPF_CNPJ_2']     ?? null,
                'Rua'             => $r['nomeDaRua']       ?? null,
                'Bairro'          => $r['nomeDoBairro']    ?? null,
                'Numero'          => $r['Numero']          ?? null,
                'Complemento'     => $r['Complemento']     ?? null,
                'nomeDoMunicipio' => $r['nomeDoMunicipio'] ?? null,
            ];
        }, $rows);
    }

    public function consultarPorLigacao(string $ligacao): array
    {
        $rows = $this->clienteRepository->findByLigacao($ligacao);

        return array_map(function ($row) {
            $r = (array) $row;
            return [
                'Ligacao'         => $r['Ligacao']        ?? null,
                'DV'              => $r['DV']             ?? null,
                'Nome'            => $r['Nome']           ?? null,
                'CPF_CNPJ'        => $r['CPF_CNPJ']       ?? null,
                'CPF_CNPJ_2'      => $r['CPF_CNPJ_2']     ?? null,
                'Rua'             => $r['nomeDaRua']       ?? null,
                'Bairro'          => $r['nomeDoBairro']    ?? null,
                'Numero'          => $r['Numero']          ?? null,
                'Complemento'     => $r['Complemento']     ?? null,
                'nomeDoMunicipio' => $r['nomeDoMunicipio'] ?? null,
            ];
        }, $rows);
    }
}