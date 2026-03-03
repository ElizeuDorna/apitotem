<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GrupoController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $query = Grupo::with(['departamento', 'empresa:id,cnpj_cpf'])->withCount('produtos');

        if (! $user->isDefaultAdmin()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $grupos = $query->paginate(15);
        return view('admin.grupos.index', compact('grupos'));
    }

    public function create()
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $departamentos = Departamento::query()
            ->when(! $user->isDefaultAdmin(), fn ($query) => $query->where('empresa_id', $user->empresa_id))
            ->with('empresa:id,cnpj_cpf')
            ->get();

        return view('admin.grupos.create', compact('departamentos'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'departamento_id' => 'required|exists:departamentos,id',
        ]);

        $departamento = Departamento::findOrFail($validated['departamento_id']);

        if (! $user->isDefaultAdmin()) {
            abort_unless($departamento->empresa_id === $user->empresa_id, 403);
            $validated['empresa_id'] = $user->empresa_id;
        } else {
            $validated['empresa_id'] = $departamento->empresa_id;
        }

        Grupo::create($validated);

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo criado com sucesso');
    }

    public function edit(Grupo $grupo)
    {
        $this->authorizeGrupoAccess($grupo);

        $user = Auth::user();

        $departamentos = Departamento::query()
            ->when(! $user->isDefaultAdmin(), fn ($query) => $query->where('empresa_id', $user->empresa_id))
            ->with('empresa:id,cnpj_cpf')
            ->get();

        return view('admin.grupos.edit', compact('grupo', 'departamentos'));
    }

    public function update(Request $request, Grupo $grupo)
    {
        $user = Auth::user();

        $this->authorizeGrupoAccess($grupo);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'departamento_id' => 'required|exists:departamentos,id',
        ]);

        $departamento = Departamento::findOrFail($validated['departamento_id']);

        if (! $user->isDefaultAdmin()) {
            abort_unless($departamento->empresa_id === $user->empresa_id, 403);
            $validated['empresa_id'] = $user->empresa_id;
        } else {
            $validated['empresa_id'] = $departamento->empresa_id;
        }

        $grupo->update($validated);

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo atualizado com sucesso');
    }

    public function destroy(Grupo $grupo)
    {
        $this->authorizeGrupoAccess($grupo);

        $nome = $grupo->nome;

        if ($grupo->produtos()->count() > 0) {
            return back()->withErrors(['delete' => "Não é possível deletar grupo com produtos associados"]);
        }

        $grupo->delete();

        return redirect()->route('admin.grupos.index')->with('success', "Grupo {$nome} deletado com sucesso");
    }

    private function authorizeGrupoAccess(Grupo $grupo): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        abort_unless($user->empresa_id && $grupo->empresa_id === $user->empresa_id, 403);
    }
}
