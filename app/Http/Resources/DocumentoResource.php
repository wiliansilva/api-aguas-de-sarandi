<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $r = (array) $this->resource;

        return [
            'referencia' => $r['referencia'] ?? null,
            'vencimento' => $r['vencimento'] ?? null,
            'valor'      => $r['valor'] !== null ? (float) $r['valor'] : null,
            'qrCode'     => $r['qrCode']     ?? null,
        ];
    }
}