<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Support\EmpresaContext;
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

    public function edit(Grupo $grupo)
    {
        $this->authorizeGrupoAccess($grupo);

        return view('admin.grupos.edit', compact('grupo'));
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
