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
    type: 'object'
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
    type: 'object'
)]
class TvCheckActivationPayloadSchema
{
}

#[OA\Schema(
    schema: 'TvHeartbeatPayload',
    properties: [
        new OA\Property(property: 'token', type: 'string', example: 'TOKEN_DA_TV', nullable: true)
    ],
    type: 'object'
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


