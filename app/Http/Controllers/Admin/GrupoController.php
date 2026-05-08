<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\Departamento;
use App\Services\GrupoService;
use App\Support\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GrupoController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $query = Grupo::with(['departamento', 'empresa:id,cnpj_cpf'])->withCount('produtos');

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $grupos = $query->paginate(15);
        return view('admin.grupos.index', compact('grupos'));
    }

    public function create()
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar grupo.');
        }

        $departamentos = Departamento::query()
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->with('empresa:id,cnpj_cpf')
            ->get();

        return view('admin.grupos.create', compact('departamentos'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar grupo.');
        }

        app(GrupoService::class)->createForEmpresa($empresaId, $request->only('nome', 'departamento_id'));

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo criado com sucesso');
    }

    public function edit(Grupo $grupo)
    {
        $this->authorizeGrupoAccess($grupo);

        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        $departamentos = Departamento::query()
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->with('empresa:id,cnpj_cpf')
            ->get();

        return view('admin.grupos.edit', compact('grupo', 'departamentos'));
    }

    public function update(Request $request, Grupo $grupo)
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para atualizar grupo.');
        }

        $this->authorizeGrupoAccess($grupo);

        app(GrupoService::class)->updateForEmpresa($grupo, $empresaId, $request->only('nome', 'departamento_id'));

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
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if ($user->isDefaultAdmin()) {
            if ($empresaId) {
                abort_unless((int) $grupo->empresa_id === (int) $empresaId, 403);
            }
            return;
        }

        abort_unless($empresaId && $grupo->empresa_id === $empresaId, 403);
    }
}
