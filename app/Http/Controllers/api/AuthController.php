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
        summary: 'Autentica empresa para acesso externo a API publica ou valida token existente',
        description: 'Este endpoint e destinado ao acesso externo da API publica, como apps desktop e outras integracoes. Forma principal de uso: enviar cnpj_cpf ou cnpj junto com senha da integracao API cadastrada na empresa. Formas alternativas: enviar {"token":"TOKEN_DA_EMPRESA"} no body, enviar {"api_token":"TOKEN_DA_EMPRESA"} no body, ou enviar o header Authorization: Bearer TOKEN_DA_EMPRESA. Apos autenticar, reutilize o token retornado no header Authorization das rotas protegidas.',
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
                            example: 'cnpj_cpf_senha',
                            summary: 'Login principal com cnpj_cpf e senha da integracao API',
                            value: [
                                'cnpj_cpf' => '12345678000199',
                                'senha' => 'minhaSenhaApi123',
                            ]
                        ),
                        new OA\Examples(
                            example: 'cnpj_senha',
                            summary: 'Login com alias cnpj e senha',
                            value: [
                                'cnpj' => '12345678000199',
                                'senha' => 'minhaSenhaApi123',
                            ]
                        ),
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
