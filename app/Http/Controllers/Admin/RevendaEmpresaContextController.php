<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RevendaEmpresaContextController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! EmpresaContext::requiresSelection($user)) {
            return redirect()->route('dashboard');
        }

        $revendaId = (int) $user->empresa->id;

        $empresas = Empresa::query()
            ->where('revenda_id', $revendaId)
            ->orderBy('nome')
            ->get(['id', 'nome', 'razaosocial', 'cnpj_cpf', 'email']);

        $empresaAtivaId = EmpresaContext::resolveEmpresaIdForUser($user);

        return view('admin.revenda.empresas-context', [
            'empresas' => $empresas,
            'empresaAtivaId' => $empresaAtivaId,
        ]);
    }

    public function acessar(Empresa $empresa): RedirectResponse
    {
        $user = Auth::user();

        abort_unless(EmpresaContext::requiresSelection($user), 403);

        abort_unless(EmpresaContext::setActiveEmpresa($user, $empresa), 403);

        return redirect()->route('dashboard')
            ->with('success', 'Empresa selecionada com sucesso. Agora use os menus no topo para configurar esta empresa.');
    }
}
