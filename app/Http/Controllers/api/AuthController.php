<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmpresaLoginRequest;
use App\Services\Api\EmpresaAuthService;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(private readonly EmpresaAuthService $authService)
    {
    }

    #[OA\Post(
        path: '/api/login',
        tags: ['Auth'],
        summary: 'Autentica empresa e retorna token da API',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/EmpresaLoginPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login realizado com sucesso'),
            new OA\Response(response: 401, description: 'Credenciais inválidas'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function login(EmpresaLoginRequest $request)
    {
        $documento = (string) ($request->input('cnpj') ?? $request->input('cnpj_cpf'));
        $senha = (string) ($request->input('senha') ?? $request->input('chave'));

        $empresa = $this->authService->attemptLogin($documento, $senha);

        if (! $empresa) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Credenciais inválidas.',
            ], 401);
        }

        $empresa = $this->authService->refreshToken($empresa);

        return response()->json([
            'sucesso' => true,
            'token' => $empresa->api_token,
            'empresa' => [
                'id' => $empresa->id,
                'nome' => $empresa->nome,
                'cnpj_cpf' => $this->authService->normalizeDocumento((string) $empresa->cnpj_cpf),
            ],
        ]);
    }
}
