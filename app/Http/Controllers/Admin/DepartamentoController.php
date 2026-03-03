<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class DepartamentoController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $query = Departamento::with('empresa:id,cnpj_cpf')->withCount('grupos', 'produtos');

        if (! $user->isDefaultAdmin()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        $departamentos = $query->paginate(15);
        return view('admin.departamentos.index', compact('departamentos'));
    }

    public function create()
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $empresaCnpjCpf = null;

        if ($user->empresa_id) {
            $empresaCnpjCpf = optional($user->empresa)->cnpj_cpf;
        }

        return view('admin.departamentos.create', compact('empresaCnpjCpf'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->isDefaultAdmin() && ! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $empresaId = $user->isDefaultAdmin() ? null : $user->empresa_id;

        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departamentos', 'nome')->where(function ($query) use ($empresaId) {
                    if ($empresaId === null) {
                        $query->whereNull('empresa_id');
                    } else {
                        $query->where('empresa_id', $empresaId);
                    }
                }),
            ],
        ]);

        Departamento::create(array_merge($validated, [
            'empresa_id' => $empresaId,
        ]));

        return redirect()->route('admin.departamentos.index')->with('success', 'Departamento criado com sucesso');
    }

    public function edit(Departamento $departamento)
    {
        $this->authorizeDepartamentoAccess($departamento);

        $departamento->loadMissing('empresa');

        return view('admin.departamentos.edit', compact('departamento'));
    }

    public function update(Request $request, Departamento $departamento)
    {
        $this->authorizeDepartamentoAccess($departamento);

        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departamentos', 'nome')
                    ->ignore($departamento->id)
                    ->where(function ($query) use ($departamento) {
                        if ($departamento->empresa_id === null) {
                            $query->whereNull('empresa_id');
                        } else {
                            $query->where('empresa_id', $departamento->empresa_id);
                        }
                    }),
            ],
        ]);

        $departamento->update($validated);

        return redirect()->route('admin.departamentos.index')->with('success', 'Departamento atualizado com sucesso');
    }

    public function destroy(Departamento $departamento)
    {
        $this->authorizeDepartamentoAccess($departamento);

        $nome = $departamento->nome;
        
        if ($departamento->produtos()->count() > 0 || $departamento->grupos()->count() > 0) {
            return back()->withErrors(['delete' => "Não é possível deletar departamento com produtos ou grupos associados"]);
        }

        $departamento->delete();

        return redirect()->route('admin.departamentos.index')->with('success', "Departamento {$nome} deletado com sucesso");
    }

    private function authorizeDepartamentoAccess(Departamento $departamento): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        abort_unless($user->empresa_id && $departamento->empresa_id === $user->empresa_id, 403);
    }
}
