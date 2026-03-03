<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '2.0.0',
    description: 'Documentação da API Totem com autenticação por token de empresa e token de dispositivo (TV).',
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
    bearerFormat: 'Token'
)]
#[OA\SecurityScheme(
    securityScheme: 'DeviceBearer',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token'
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
        new OA\Schema(required: ['cnpj', 'senha']),
        new OA\Schema(required: ['cnpj', 'chave']),
        new OA\Schema(required: ['cnpj_cpf', 'senha']),
        new OA\Schema(required: ['cnpj_cpf', 'chave']),
    ],
    properties: [
        new OA\Property(property: 'cnpj', type: 'string', example: '12345678000199'),
        new OA\Property(property: 'cnpj_cpf', type: 'string', example: '12345678000199'),
        new OA\Property(property: 'senha', type: 'string', example: 'senha_da_empresa'),
        new OA\Property(property: 'chave', type: 'string', example: 'senha_da_empresa')
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


