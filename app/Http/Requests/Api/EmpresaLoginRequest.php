<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EmpresaLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cnpj' => ['required_without:cnpj_cpf', 'string', 'max:18'],
            'cnpj_cpf' => ['required_without:cnpj', 'string', 'max:18'],
            'senha' => ['required_without:chave', 'string', 'min:6', 'max:120'],
            'chave' => ['required_without:senha', 'string', 'min:6', 'max:120'],
        ];
    }
}
