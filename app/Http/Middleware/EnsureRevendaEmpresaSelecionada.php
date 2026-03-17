<?php

namespace App\Http\Middleware;

use App\Support\EmpresaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRevendaEmpresaSelecionada
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! EmpresaContext::requiresSelection($user)) {
            return $next($request);
        }

        $allowedRoutes = [
            'logout',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'admin.revenda.empresas.index',
            'admin.revenda.empresas.acessar',
            'admin.empresas.index',
            'admin.empresas.create',
            'admin.empresas.store',
            'admin.empresas.edit',
            'admin.empresas.update',
            'admin.empresas.destroy',
            'admin.empresas.selecionar',
            'admin.empresas.selecionar.get',
            'admin.empresas.limpar-selecao',
        ];

        $routeName = (string) optional($request->route())->getName();

        if (in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        if (EmpresaContext::resolveEmpresaIdForUser($user)) {
            return $next($request);
        }

        return redirect()
            ->route('admin.empresas.index')
            ->with('warning', 'Selecione uma empresa para continuar.');
    }
}
