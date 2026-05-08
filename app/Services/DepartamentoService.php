<?php

namespace App\Services;

use App\Models\Departamento;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartamentoService
{
    public function createForEmpresa(int $empresaId, array $data): Departamento
    {
        $validated = Validator::make(
            $data,
            [
                'nome' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('departamentos', 'nome')->where(function ($query) use ($empresaId) {
                        $query->where('empresa_id', $empresaId);
                    }),
                ],
            ]
        )->validate();

        return Departamento::query()->create([
            'empresa_id' => $empresaId,
            'nome' => trim($validated['nome']),
        ]);
    }
}