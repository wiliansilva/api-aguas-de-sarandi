<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConsultaDocumentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O parâmetro "ligacao" vem da rota (route parameter), não do body.
     * Precisamos mesclá-lo nas regras de validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ligacao' => $this->route('ligacao'),
        ]);
    }

    public function rules(): array
    {
        return [
            'ligacao' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ligacao.required' => 'O parâmetro "ligacao" é obrigatório.',
            'ligacao.integer'  => 'O parâmetro "ligacao" deve ser um número inteiro.',
            'ligacao.min'      => 'O parâmetro "ligacao" deve ser maior que zero.',
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