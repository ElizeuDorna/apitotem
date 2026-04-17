<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '2.0.0',
    description: 'Documentação da API Totem com autenticação por token de empresa e token de dispositivo (TV).' . "\n\n" .
        'Como usar o token da empresa:' . "\n" .
        '- Em chamadas HTTP reais envie `Authorization: Bearer TOKEN_DA_EMPRESA`.' . "\n" .
        '- No botão `Authorize` do Swagger, informe apenas `TOKEN_DA_EMPRESA`.' . "\n" .
        '- No endpoint `/api/login`, também é aceito body com `{\"token\":\"TOKEN_DA_EMPRESA\"}` ou `{\"api_token\":\"TOKEN_DA_EMPRESA\"}`.' . "\n\n" .
        'Como usar o token do dispositivo TV:' . "\n" .
        '- Em chamadas HTTP reais envie `Authorization: Bearer TOKEN_DA_TV`.' . "\n" .
        '- No botão `Authorize` do Swagger, informe apenas `TOKEN_DA_TV`.',
    title: 'API Totem'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Servidor local'
)]
#[OA\SecurityScheme(
    securityScheme: 'CompanyBearer',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Autenticacao da empresa. Em chamadas HTTP reais envie o header Authorization: Bearer TOKEN_DA_EMPRESA. No botao Authorize do Swagger, informe apenas o valor do token, sem o prefixo Bearer.'
)]
#[OA\SecurityScheme(
    securityScheme: 'DeviceBearer',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Autenticacao do dispositivo TV. Em chamadas HTTP reais envie o header Authorization: Bearer TOKEN_DA_TV. No botao Authorize do Swagger, informe apenas o valor do token, sem o prefixo Bearer.'
)]
class ApiDocumentation
{
}

#[OA\Schema(
    schema: 'ProdutoPayload',
    required: ['NOME', 'PRECO', 'departamento_id', 'grupo_id'],
    properties: [
        new OA\Property(property: 'CODIGO', type: 'string', maxLength: 14),
        new OA\Property(property: 'NOME', type: 'string'),
        new OA\Property(property: 'PRECO', type: 'number', format: 'float'),
        new OA\Property(property: 'OFERTA', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'IMG', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'departamento_id', type: 'integer'),
        new OA\Property(property: 'grupo_id', type: 'integer')
    ],
    type: 'object'
)]
class ProdutoPayloadSchema
{
}

#[OA\Schema(
    schema: 'DepartamentoPayload',
    required: ['nome'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Bebidas')
    ],
    type: 'object',
    example: [
        'nome' => 'Bebidas'
    ]
)]
class DepartamentoPayloadSchema
{
}

#[OA\Schema(
    schema: 'GrupoPayload',
    required: ['departamento_id', 'nome'],
    properties: [
        new OA\Property(property: 'departamento_id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Refrigerantes')
    ],
    type: 'object',
    example: [
        'departamento_id' => 1,
        'nome' => 'Refrigerantes'
    ]
)]
class GrupoPayloadSchema
{
}

#[OA\Schema(
    schema: 'ConfiguracaoPayload',
    properties: [
        new OA\Property(property: 'apiUrl', type: 'string', maxLength: 500, example: 'https://api.seudominio.com/api/produtos', nullable: true),
        new OA\Property(property: 'apiRefreshInterval', type: 'integer', example: 30, nullable: true),
        new OA\Property(property: 'priceColor', type: 'string', example: '#ffffff', nullable: true),
        new OA\Property(property: 'offerColor', type: 'string', example: '#facc15', nullable: true),
        new OA\Property(property: 'rowBackgroundColor', type: 'string', example: '#0f172a', nullable: true),
        new OA\Property(property: 'showBorder', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'borderColor', type: 'string', example: '#334155', nullable: true),
        new OA\Property(property: 'useGradient', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'gradientStartColor', type: 'string', example: '#0f172a', nullable: true),
        new OA\Property(property: 'gradientEndColor', type: 'string', example: '#1e293b', nullable: true),
        new OA\Property(property: 'gradientStop1', type: 'number', format: 'float', example: 0.2, nullable: true),
        new OA\Property(property: 'gradientStop2', type: 'number', format: 'float', example: 0.9, nullable: true),
        new OA\Property(property: 'appBackgroundColor', type: 'string', example: '#020617', nullable: true),
        new OA\Property(property: 'isMainBorderEnabled', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'mainBorderColor', type: 'string', example: '#000000', nullable: true),
        new OA\Property(property: 'showImage', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'imageSize', type: 'integer', example: 56, nullable: true),
        new OA\Property(property: 'isPaginationEnabled', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'pageSize', type: 'integer', example: 10, nullable: true),
        new OA\Property(property: 'paginationInterval', type: 'integer', example: 5, nullable: true)
    ],
    type: 'object',
    example: [
        'apiUrl' => 'https://api.seudominio.com/api/produtos',
        'apiRefreshInterval' => 30,
        'priceColor' => '#ffffff',
        'offerColor' => '#facc15',
        'rowBackgroundColor' => '#0f172a',
        'showBorder' => true,
        'borderColor' => '#334155',
        'useGradient' => true,
        'gradientStartColor' => '#0f172a',
        'gradientEndColor' => '#1e293b',
        'gradientStop1' => 0.2,
        'gradientStop2' => 0.9,
        'appBackgroundColor' => '#020617',
        'isMainBorderEnabled' => false,
        'mainBorderColor' => '#000000',
        'showImage' => true,
        'imageSize' => 56,
        'isPaginationEnabled' => true,
        'pageSize' => 10,
        'paginationInterval' => 5
    ]
)]
class ConfiguracaoPayloadSchema
{
}

#[OA\Schema(
    schema: 'EmpresaPayload',
    required: ['nome', 'razaosocial', 'cnpj_cpf', 'email', 'fone'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Mercado Exemplo'),
        new OA\Property(property: 'razaosocial', type: 'string', maxLength: 255, example: 'Mercado Exemplo LTDA'),
        new OA\Property(property: 'cnpj_cpf', type: 'string', maxLength: 14, example: '12345678000199'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'contato@mercadoexemplo.com'),
        new OA\Property(property: 'fone', type: 'string', maxLength: 20, example: '11999998888'),
        new OA\Property(property: 'endereco', type: 'string', maxLength: 255, example: 'Rua Central', nullable: true),
        new OA\Property(property: 'bairro', type: 'string', maxLength: 100, example: 'Centro', nullable: true),
        new OA\Property(property: 'numero', type: 'string', maxLength: 20, example: '100', nullable: true),
        new OA\Property(property: 'cep', type: 'string', maxLength: 10, example: '01001000', nullable: true)
    ],
    type: 'object',
    example: [
        'nome' => 'Mercado Exemplo',
        'razaosocial' => 'Mercado Exemplo LTDA',
        'cnpj_cpf' => '12345678000199',
        'email' => 'contato@mercadoexemplo.com',
        'fone' => '11999998888',
        'endereco' => 'Rua Central',
        'bairro' => 'Centro',
        'numero' => '100',
        'cep' => '01001000'
    ]
)]
class EmpresaPayloadSchema
{
}

#[OA\Schema(
    schema: 'EmpresaLoginPayload',
    anyOf: [
        new OA\Schema(required: ['token']),
        new OA\Schema(required: ['api_token']),
    ],
    description: 'O login pode validar o token da empresa de tres formas: body com campo token, body com campo api_token, ou header Authorization: Bearer TOKEN_DA_EMPRESA.',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: 'TOKEN_DA_EMPRESA'),
        new OA\Property(property: 'api_token', type: 'string', example: 'TOKEN_DA_EMPRESA')
    ],
    type: 'object'
)]
class EmpresaLoginPayloadSchema
{
}

#[OA\Schema(
    schema: 'TvActivationCodePayload',
    required: ['device_uuid'],
    properties: [
        new OA\Property(property: 'device_uuid', type: 'string', example: '9f23edc7-9a60-4f18-bf4b-29f5d71b7030')
    ],
    type: 'object',
    example: [
        'device_uuid' => '9f23edc7-9a60-4f18-bf4b-29f5d71b7030'
    ]
)]
class TvActivationCodePayloadSchema
{
}

#[OA\Schema(
    schema: 'TvCheckActivationPayload',
    required: ['device_uuid'],
    properties: [
        new OA\Property(property: 'device_uuid', type: 'string', example: '9f23edc7-9a60-4f18-bf4b-29f5d71b7030')
    ],
    type: 'object',
    example: [
        'device_uuid' => '9f23edc7-9a60-4f18-bf4b-29f5d71b7030'
    ]
)]
class TvCheckActivationPayloadSchema
{
}

#[OA\Schema(
    schema: 'TvHeartbeatPayload',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: 'TOKEN_DA_TV', nullable: true)
    ],
    type: 'object',
    example: [
        'token' => 'TOKEN_DA_TV'
    ]
)]
class TvHeartbeatPayloadSchema
{
}

#[OA\Schema(
    schema: 'AdminActivateDevicePayload',
    required: ['code', 'empresa_id', 'nome_tv'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: '84721'),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'nome_tv', type: 'string', example: 'TV Açougue'),
        new OA\Property(property: 'local', type: 'string', example: 'Setor Açougue', nullable: true)
    ],
    type: 'object'
)]
class AdminActivateDevicePayloadSchema
{
}


