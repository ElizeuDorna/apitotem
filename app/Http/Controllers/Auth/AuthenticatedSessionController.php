<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Configuracao;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login', [
            'showSelfServiceRegisterOnLogin' => $this->showSelfServiceRegisterOnLogin(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user && EmpresaContext::requiresSelection($user)) {
            EmpresaContext::clearActiveEmpresa();
            return redirect()->route('admin.revenda.empresas.index');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        EmpresaContext::clearActiveEmpresa();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function showSelfServiceRegisterOnLogin(): bool
    {
        if (! Schema::hasColumn('configuracoes', 'showSelfServiceRegisterOnLogin')) {
            return true;
        }

        $value = Configuracao::query()
            ->whereNull('empresa_id')
            ->value('showSelfServiceRegisterOnLogin');

        return $value === null ? true : (bool) $value;
    }
}
