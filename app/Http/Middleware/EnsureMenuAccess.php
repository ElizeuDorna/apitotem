<?php

namespace App\Http\Middleware;

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

        if (! $user || ! $user->hasMenuAccess($menu)) {
            abort(403);
        }

        return $next($request);
    }
}
