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
            'documento' => ['sometimes', 'string', 'regex:/^\d{11}$|^\d{14}$/'],
            'ligacao'   => ['sometimes', 'string', 'regex:/^0*[1-9]\d*$/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('documento') && !$this->filled('ligacao')) {
                $validator->errors()->add(
                    'parametro',
                    'Informe "documento" (CPF ou CNPJ) ou "ligacao".'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'documento.regex' => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) sem formatação.',
            'ligacao.regex'   => 'Informe um número de ligação válido (inteiro positivo).',
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