<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LinhaDigitavelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ligacao'    => ['required', 'integer', 'min:1'],
            'referencia' => ['required', 'string',  'regex:/^\d{4}$/'],
            'vencimento' => ['required', 'date_format:Y-m-d'],
            'valor'      => ['required', 'numeric',  'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'ligacao.required'    => 'O campo "ligacao" é obrigatório.',
            'ligacao.integer'     => 'O campo "ligacao" deve ser um número inteiro.',
            'ligacao.min'         => 'O campo "ligacao" deve ser maior que zero.',
            'referencia.required' => 'O campo "referencia" é obrigatório.',
            'referencia.regex'    => 'O campo "referencia" deve ter exatamente 4 dígitos (ex: 0326).',
            'vencimento.required' => 'O campo "vencimento" é obrigatório.',
            'vencimento.date_format' => 'O campo "vencimento" deve estar no formato YYYY-MM-DD.',
            'valor.required'      => 'O campo "valor" é obrigatório.',
            'valor.numeric'       => 'O campo "valor" deve ser numérico.',
            'valor.min'           => 'O campo "valor" deve ser maior que zero.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error'   => 'Parâmetros inválidos.',
                'detail'  => $validator->errors()->first(),
            ], 422)
        );
    }
}