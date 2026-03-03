<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmpresaAuthController extends Controller
{
    /**
     * Display the dual login form
     */
    public function showDualLoginForm(): View
    {
        return view('auth.dual-login');
    }

    /**
     * Handle dual login authentication
     */
    public function authenticateDual(Request $request): RedirectResponse
    {
        // Strip formatting from CPF/CNPJ
        $cpfCnpj = preg_replace('/\D/', '', (string) $request->input('cpf_cnpj'));

        $validated = $request->validate([
            'cpf_cnpj' => 'required|string',
            'email' => 'required|email|string',
            'password' => 'required|string',
        ], [
            'cpf_cnpj.required' => 'CPF ou CNPJ é obrigatório',
            'email.required' => 'Email é obrigatório',
            'email.email' => 'Email inválido',
            'password.required' => 'Senha é obrigatória',
        ]);

        // Detect if CPF (11 digits) or CNPJ (14 digits)
        $digitCount = strlen($cpfCnpj);

        if ($digitCount === 11) {
            return $this->authenticatePessoaFisica($request, $cpfCnpj, $validated);
        } elseif ($digitCount === 14) {
            return $this->authenticateEmpresa($request, $cpfCnpj, $validated);
        } else {
            return back()
                ->withInput($request->only('cpf_cnpj', 'email'))
                ->withErrors(['cpf_cnpj' => 'CPF deve ter 11 dígitos ou CNPJ deve ter 14 dígitos']);
        }
    }

    /**
     * Authenticate pessoa física
     */
    private function authenticatePessoaFisica(Request $request, string $cpf, array $validated): RedirectResponse
    {
        if (Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'cpf' => $cpf,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('cpf_cnpj', 'email'))
            ->withErrors(['authentication' => 'CPF/CNPJ, Email ou senha incorretos']);
    }

    /**
     * Authenticate empresa
     */
    private function authenticateEmpresa(Request $request, string $cnpj, array $validated): RedirectResponse
    {
        $empresa = Empresa::where('cnpj_cpf', $cnpj)->first();

        if (! $empresa) {
            return back()
                ->withInput($request->only('cpf_cnpj', 'email'))
                ->withErrors(['authentication' => 'Empresa não encontrada para o CNPJ informado']);
        }

        $user = User::where('email', $validated['email'])
            ->where('empresa_id', $empresa->id)
            ->first();

        if ($user && Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'empresa_id' => $empresa->id,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('cpf_cnpj', 'email'))
            ->withErrors(['authentication' => 'CPF/CNPJ, Email ou senha incorretos']);
    }

    /**
     * Logout empresa
     */
    public function logoutEmpresa(): RedirectResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}
