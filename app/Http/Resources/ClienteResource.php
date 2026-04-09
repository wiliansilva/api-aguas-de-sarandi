<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    /**
     * Desativa o wrapper "data" padrão do Laravel Resource,
     * pois o ApiResponse trait já cuida disso.
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'ligacao'          => $this->resource['Ligacao']         ?? null,
            'dv'               => $this->resource['DV']              ?? null,
            'nome'             => $this->resource['Nome']            ?? null,
            'cpf_cnpj'         => $this->resource['CPF_CNPJ']        ?? null,
            'cpf_cnpj_2'       => $this->resource['CPF_CNPJ_2']      ?? null,
            'rua'              => $this->resource['Rua']             ?? null,
            'bairro'           => $this->resource['Bairro']          ?? null,
            'numero'           => $this->resource['Numero']          ?? null,
            'complemento'      => $this->resource['Complemento']     ?? null,
            'municipio'        => $this->resource['nomeDoMunicipio'] ?? null,
        ];
    }
}