<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Departamento;
use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $query = Produto::with(['departamento', 'grupo']);

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $produtos = $query->paginate(15);
        $departamentos = $this->departamentosQueryForUser($user)->get();
        $grupos = $this->gruposQueryForUser($user)->get();
        return view('admin.produtos.index', compact('produtos', 'departamentos', 'grupos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $departamentos = $this->departamentosQueryForUser($user)->get();
        $grupos = $this->gruposQueryForUser($user)->get();
        return view('admin.produtos.create', compact('departamentos', 'grupos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $empresaIdForUnique = $user->isDefaultAdmin() ? null : $user->empresa_id;

        $validated = $request->validate([
            'CODIGO' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('produto', 'CODIGO')->where(function ($query) use ($empresaIdForUnique) {
                    if ($empresaIdForUnique !== null) {
                        return $query->where('empresa_id', $empresaIdForUnique);
                    }

                    return $query;
                }),
            ],
            'NOME' => 'required|string|max:255',
            'PRECO' => 'required|numeric|min:0',
            'OFERTA' => 'nullable|numeric|min:0',
            'IMG' => 'nullable|url|max:500',
            'departamento_id' => 'required|exists:departamentos,id',
            'grupo_id' => 'required|exists:grupos,id',
        ]);

        // Validate that grupo belongs to departamento
        $grupo = Grupo::find($validated['grupo_id']);
        if ($grupo->departamento_id != $validated['departamento_id']) {
            return back()->withErrors(['grupo_id' => 'Grupo deve pertencer ao departamento selecionado']);
        }

        $departamento = Departamento::find($validated['departamento_id']);

        if (! $departamento) {
            abort(403);
        }

        $empresaId = $user->isDefaultAdmin() ? $departamento->empresa_id : $user->empresa_id;

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation()) {
            if (! $departamento || $departamento->empresa_id !== $user->empresa_id || $grupo->empresa_id !== $user->empresa_id) {
                abort(403);
            }
        }

        $validated['empresa_id'] = $empresaId;

        Produto::create($validated);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto criado com sucesso');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Produto $produto)
    {
        $this->authorizeProdutoAccess($produto);

        $user = Auth::user();

        $departamentos = $this->departamentosQueryForUser($user)->get();
        $grupos = $this->gruposQueryForUser($user)->get();
        return view('admin.produtos.edit', compact('produto', 'departamentos', 'grupos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Produto $produto)
    {
        $user = Auth::user();

        $this->authorizeProdutoAccess($produto);

        $empresaIdForUnique = $user->isDefaultAdmin()
            ? ($produto->empresa_id ?? optional(Departamento::find($request->input('departamento_id', $produto->departamento_id)))->empresa_id)
            : $user->empresa_id;

        $validated = $request->validate([
            'CODIGO' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('produto', 'CODIGO')
                    ->where(function ($query) use ($empresaIdForUnique) {
                        if ($empresaIdForUnique !== null) {
                            return $query->where('empresa_id', $empresaIdForUnique);
                        }

                        return $query;
                    })
                    ->ignore($produto->id),
            ],
            'NOME' => 'required|string|max:255',
            'PRECO' => 'required|numeric|min:0',
            'OFERTA' => 'nullable|numeric|min:0',
            'IMG' => 'nullable|url|max:500',
            'departamento_id' => 'required|exists:departamentos,id',
            'grupo_id' => 'required|exists:grupos,id',
        ]);

        // Validate that grupo belongs to departamento
        $grupo = Grupo::find($validated['grupo_id']);
        if ($grupo->departamento_id != $validated['departamento_id']) {
            return back()->withErrors(['grupo_id' => 'Grupo deve pertencer ao departamento selecionado']);
        }

        $departamento = Departamento::find($validated['departamento_id']);

        if (! $departamento) {
            abort(403);
        }

        $empresaId = $user->isDefaultAdmin() ? $departamento->empresa_id : $user->empresa_id;

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation()) {
            if (! $departamento || $departamento->empresa_id !== $user->empresa_id || $grupo->empresa_id !== $user->empresa_id) {
                abort(403);
            }
        }

        $validated['empresa_id'] = $empresaId;

        $produto->update($validated);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado com sucesso');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Produto $produto)
    {
        $this->authorizeProdutoAccess($produto);

        $codigo = $produto->CODIGO;
        $produto->delete();

        return redirect()->route('admin.produtos.index')->with('success', "Produto {$codigo} deletado com sucesso");
    }

    private function authorizeProdutoAccess(Produto $produto): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        if ($this->usesEmpresaSegregation()) {
            $produto->loadMissing(['departamento:id,empresa_id', 'grupo:id,empresa_id']);

            $empresaId = $produto->empresa_id ?? $produto->departamento?->empresa_id ?? $produto->grupo?->empresa_id;

            if ($empresaId !== null) {
                abort_unless((int) $user->empresa_id === (int) $empresaId, 403);
                return;
            }
        }

        abort(403);
    }

    private function usesEmpresaSegregation(): bool
    {
        return Schema::hasColumn('departamentos', 'empresa_id')
            && Schema::hasColumn('grupos', 'empresa_id')
            && Schema::hasColumn('produto', 'empresa_id');
    }

    private function departamentosQueryForUser($user)
    {
        return Departamento::query()
            ->when(
                ! $user->isDefaultAdmin() && $this->usesEmpresaSegregation(),
                fn ($query) => $query->where('empresa_id', $user->empresa_id)
            );
    }

    private function gruposQueryForUser($user)
    {
        return Grupo::query()
            ->when(
                ! $user->isDefaultAdmin() && $this->usesEmpresaSegregation(),
                fn ($query) => $query->where('empresa_id', $user->empresa_id)
            );
    }
}
