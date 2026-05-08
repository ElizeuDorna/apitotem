<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\EmpresaService;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.empresas.index');
    }

    public function selecionarEmpresaAtiva(Request $request, Empresa $empresa): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($user->isDefaultAdmin() || EmpresaContext::requiresSelection($user), 403);
        abort_unless(EmpresaContext::setActiveEmpresa($user, $empresa), 403);

        return redirect()
            ->route('admin.empresas.index', $request->query())
            ->with('success', 'Empresa ativa selecionada com sucesso.');
    }

    public function limparEmpresaAtiva(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($user->isDefaultAdmin() || EmpresaContext::requiresSelection($user), 403);

        if ($user->isDefaultAdmin()) {
            session()->forget(EmpresaContext::ADMIN_SESSION_KEY);
        } else {
            session()->forget(EmpresaContext::SESSION_KEY);
        }

        return redirect()
            ->route('admin.empresas.index', $request->query())
            ->with('success', 'Empresa ativa removida.');
    }

    public function edit(Empresa $empresa, Request $request, EmpresaService $empresaService)
    {
        $empresaService->authorizeEmpresaAccess(Auth::user(), $empresa);

        return view('admin.empresas.edit', [
            'empresa' => $empresa,
            'returnUrl' => (string) $request->query('return', route('admin.empresas.index')),
        ]);
    }

}
