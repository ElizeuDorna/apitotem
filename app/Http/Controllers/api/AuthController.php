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
        summary: 'Valida o token da empresa e mantém compatibilidade temporária com login legado',
        description: 'Formas recomendadas de uso: 1) enviar {"token":"TOKEN_DA_EMPRESA"} no body; 2) enviar {"api_token":"TOKEN_DA_EMPRESA"} no body; 3) enviar o header Authorization: Bearer TOKEN_DA_EMPRESA. Apos validar, reutilize o mesmo token no header Authorization das rotas protegidas.',
        parameters: [
            new OA\Parameter(
                name: 'Authorization',
                in: 'header',
                required: false,
                description: 'Opcional no /api/login. Exemplo de uso real: Bearer TOKEN_DA_EMPRESA',
                schema: new OA\Schema(type: 'string', example: 'Bearer TOKEN_DA_EMPRESA')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/EmpresaLoginPayload'),
                    examples: [
                        new OA\Examples(
                            example: 'token',
                            summary: 'Login com campo token',
                            value: ['token' => 'TOKEN_DA_EMPRESA']
                        ),
                        new OA\Examples(
                            example: 'api_token',
                            summary: 'Login com campo api_token',
                            value: ['api_token' => 'TOKEN_DA_EMPRESA']
                        ),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token validado com sucesso'),
            new OA\Response(response: 401, description: 'Token ou credenciais inválidos'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function login(EmpresaLoginRequest $request)
    {
        $token = (string) ($request->bearerToken() ?? $request->input('token') ?? $request->input('api_token') ?? '');

        if ($token !== '') {
            $empresa = $this->authService->findByToken($token);

            if (! $empresa) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Token inválido.',
                ], 401);
            }

            $empresa = $this->authService->ensureApiToken($empresa);

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

        $documento = (string) ($request->input('cnpj') ?? $request->input('cnpj_cpf'));
        $senha = (string) ($request->input('senha') ?? $request->input('chave'));

        $empresa = $this->authService->attemptPasswordLogin($documento, $senha);

        if (! $empresa) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Token ou credenciais inválidos.',
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
