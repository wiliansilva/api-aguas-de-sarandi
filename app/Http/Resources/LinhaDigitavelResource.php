<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinhaDigitavelResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'linhaDigitavel' => $this->resource['linhaDigitavel'] ?? null,
            'qrCodeImagem'   => $this->resource['qrCodeImagem']   ?? null,
        ];
    }
}
