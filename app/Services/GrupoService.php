<?php

namespace App\Services;

use App\Models\Departamento;
use App\Models\Grupo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrupoService
{
    public function createForEmpresa(int $empresaId, array $data): Grupo
    {
        $validated = $this->validate($empresaId, $data);

        return Grupo::query()->create([
            'empresa_id' => $empresaId,
            'nome' => trim($validated['nome']),
            'departamento_id' => (int) $validated['departamento_id'],
        ]);
    }

    public function updateForEmpresa(Grupo $grupo, int $empresaId, array $data): Grupo
    {
        $validated = $this->validate($empresaId, $data, $grupo);

        $grupo->update([
            'empresa_id' => $empresaId,
            'nome' => trim($validated['nome']),
            'departamento_id' => (int) $validated['departamento_id'],
        ]);

        return $grupo;
    }

    private function validate(int $empresaId, array $data, ?Grupo $grupo = null): array
    {
        $validated = Validator::make(
            $data,
            [
                'nome' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('grupos', 'nome')
                        ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                        ->ignore($grupo?->id),
                ],
                'departamento_id' => ['required', 'integer', 'exists:departamentos,id'],
            ],
            [
                'nome.unique' => 'Ja existe um grupo com este nome para esta empresa.',
                'departamento_id.exists' => 'Departamento nao encontrado.',
            ]
        )->validate();

        $departamento = Departamento::query()->findOrFail($validated['departamento_id']);

        if ((int) $departamento->empresa_id !== $empresaId) {
            throw ValidationException::withMessages([
                'departamento_id' => 'Departamento nao pertence a empresa ativa.',
            ]);
        }

        return $validated;
    }
}