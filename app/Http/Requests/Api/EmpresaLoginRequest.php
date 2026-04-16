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
            'cnpj' => ['required_without_all:cnpj_cpf,token,api_token', 'nullable', 'string', 'max:18'],
            'cnpj_cpf' => ['required_without_all:cnpj,token,api_token', 'nullable', 'string', 'max:18'],
            'senha' => ['required_without_all:token,api_token,chave', 'nullable', 'string', 'min:6', 'max:120'],
            'chave' => ['required_without_all:token,api_token,senha', 'nullable', 'string', 'min:6', 'max:120'],
            'token' => ['required_without_all:cnpj,cnpj_cpf,senha,chave,api_token', 'nullable', 'string', 'max:120'],
            'api_token' => ['required_without_all:cnpj,cnpj_cpf,senha,chave,token', 'nullable', 'string', 'max:120'],
        ];
    }
}
