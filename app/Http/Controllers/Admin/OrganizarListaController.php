<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Departamento;
use App\Models\Grupo;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizarListaController extends Controller
{
    public function edit(): View
    {
        $empresaId = $this->resolveEmpresaId();

        $config = Configuracao::query()->firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $departamentos = Departamento::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $grupos = Grupo::query()
            ->with('departamento:id,nome')
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome', 'departamento_id']);

        $orderedDepartamentos = $this->orderByConfiguredIds(
            $departamentos,
            $this->normalizeIdList($config->productDepartmentOrder ?? [])
        );

        $orderedGrupos = $this->orderByConfiguredIds(
            $grupos,
            $this->normalizeIdList($config->productGroupOrder ?? [])
        );

        return view('admin.organizar-lista', [
            'config' => $config,
            'departamentos' => $orderedDepartamentos,
            'grupos' => $orderedGrupos,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $empresaId = $this->resolveEmpresaId();

        $validated = $request->validate([
            'productListOrderMode' => ['required', 'in:grupo,departamento'],
            'productAlphabeticalDirection' => ['required', 'in:asc,desc'],
            'productDepartmentOrder' => ['nullable', 'string'],
            'productGroupOrder' => ['nullable', 'string'],
        ]);

        $departmentOrder = $this->normalizeIdList($this->decodeJsonList($validated['productDepartmentOrder'] ?? ''));
        $groupOrder = $this->normalizeIdList($this->decodeJsonList($validated['productGroupOrder'] ?? ''));

        Configuracao::query()->updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'productListOrderMode' => $validated['productListOrderMode'],
                'productAlphabeticalDirection' => $validated['productAlphabeticalDirection'],
                'productDepartmentOrder' => $departmentOrder,
                'productGroupOrder' => $groupOrder,
            ]
        );

        return redirect()
            ->back()
            ->with('success', 'Organização da lista atualizada com sucesso.');
    }

    private function resolveEmpresaId(): int
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        return EmpresaContext::requireEmpresaId($user);
    }

    private function decodeJsonList(string $value): array
    {
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeIdList(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function orderByConfiguredIds(Collection $items, array $configuredIds): Collection
    {
        if (empty($configuredIds)) {
            return $items->values();
        }

        $byId = $items->keyBy('id');
        $ordered = collect($configuredIds)
            ->map(fn ($id) => $byId->get((int) $id))
            ->filter();

        $remaining = $items->filter(fn ($item) => !in_array((int) $item->id, $configuredIds, true));

        return $ordered->concat($remaining)->values();
    }
}
