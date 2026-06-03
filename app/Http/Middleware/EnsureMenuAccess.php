<?php

namespace App\Http\Middleware;

use App\Support\EmpresaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $menu): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($menu === 'empresas' && EmpresaContext::requiresSelection($user)) {
            return $next($request);
        }

        if (! $user->hasMenuAccess($menu)) {
            abort(403);
        }

        return $next($request);
    }
}
