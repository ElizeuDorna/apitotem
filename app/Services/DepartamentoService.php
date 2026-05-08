<?php

namespace App\Services;

use App\Models\Departamento;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartamentoService
{
    public function createForEmpresa(int $empresaId, array $data): Departamento
    {
        $validated = $this->validate($empresaId, $data);

        return Departamento::query()->create([
            'empresa_id' => $empresaId,
            'nome' => trim($validated['nome']),
        ]);
    }

    public function updateForEmpresa(Departamento $departamento, int $empresaId, array $data): Departamento
    {
        $validated = $this->validate($empresaId, $data, $departamento);

        $departamento->update([
            'nome' => trim($validated['nome']),
        ]);

        return $departamento;
    }

    private function validate(int $empresaId, array $data, ?Departamento $departamento = null): array
    {
        $validated = Validator::make(
            $data,
            [
                'nome' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('departamentos', 'nome')
                        ->where(function ($query) use ($empresaId) {
                            $query->where('empresa_id', $empresaId);
                        })
                        ->ignore($departamento?->id),
                ],
            ]
        )->validate();

        return $validated;
    }
}