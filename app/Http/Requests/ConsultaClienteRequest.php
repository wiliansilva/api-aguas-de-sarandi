<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConsultaClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza o documento antes de validar (remove formatação).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('documento')) {
            $this->merge([
                'documento' => preg_replace('/\D/', '', $this->documento),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'documento' => [
                'required',
                'string',
                'regex:/^\d{11}$|^\d{14}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'documento.required' => 'O parâmetro "documento" é obrigatório.',
            'documento.regex'    => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) sem formatação.',
        ];
    }

    /**
     * Retorna erro em JSON ao invés de redirecionar (padrão de API).
     */
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