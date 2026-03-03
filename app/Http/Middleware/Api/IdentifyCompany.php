<?php

namespace App\Http\Middleware\Api;

use App\Services\Api\EmpresaAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyCompany
{
    public function __construct(private readonly EmpresaAuthService $authService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token não informado.',
            ], 401);
        }

        $empresa = $this->authService->findByToken($token);

        if (! $empresa) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token inválido ou empresa inativa.',
            ], 401);
        }

        $request->attributes->set('empresa', $empresa);

        return $next($request);
    }
}
