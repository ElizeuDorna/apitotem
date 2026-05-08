<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use App\Support\EmpresaContext;
use Illuminate\Support\Facades\Auth;

class DepartamentoController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $query = Departamento::with('empresa:id,cnpj_cpf')->withCount('grupos', 'produtos');

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $departamentos = $query->paginate(15);
        return view('admin.departamentos.index', compact('departamentos'));
    }

    public function edit(Departamento $departamento)
    {
        $this->authorizeDepartamentoAccess($departamento);

        $departamento->loadMissing('empresa');

        return view('admin.departamentos.edit', compact('departamento'));
    }

    private function authorizeDepartamentoAccess(Departamento $departamento): void
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if ($user->isDefaultAdmin()) {
            if ($empresaId) {
                abort_unless((int) $departamento->empresa_id === (int) $empresaId, 403);
            }
            return;
        }

        abort_unless($empresaId && $departamento->empresa_id === $empresaId, 403);
    }
}
