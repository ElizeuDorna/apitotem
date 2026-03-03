<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ConfiguracaoResource;
use App\Models\Configuracao;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConfiguracaoController extends Controller
{
    /**
     * Get the current configuration.
     * GET /api/configuracao
     */
    #[OA\Get(
        path: '/api/configuracoes',
        tags: ['Configurações'],
        summary: 'Obtém configuração da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    #[OA\Get(
        path: '/api/configuracao',
        tags: ['Configurações'],
        summary: 'Obtém configuração da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('empresa');

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresa->id,
        ], [
            'empresa_id' => $empresa->id,
        ])->fresh();

        return response()->json([
            'sucesso' => true,
            'dados' => new ConfiguracaoResource($config)
        ], 200);
    }

    /**
     * Update the configuration.
     * POST/PUT /api/configuracao
     */
    #[OA\Post(
        path: '/api/configuracoes',
        tags: ['Configurações'],
        summary: 'Salva configuração da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Configuração salva'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    #[OA\Post(
        path: '/api/configuracao',
        tags: ['Configurações'],
        summary: 'Salva configuração da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Configuração salva'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(Request $request)
    {
        $empresa = $request->attributes->get('empresa');

        $validated = $request->validate([
            'apiUrl' => 'nullable|string|max:500',
            'priceColor' => 'nullable|string|max:7',
            'offerColor' => 'nullable|string|max:7',
            'rowBackgroundColor' => 'nullable|string|max:9',
            'showBorder' => 'nullable|boolean',
            'borderColor' => 'nullable|string|max:7',
            'useGradient' => 'nullable|boolean',
            'gradientStartColor' => 'nullable|string|max:7',
            'gradientEndColor' => 'nullable|string|max:7',
            'gradientStop1' => 'nullable|numeric|min:0|max:1',
            'gradientStop2' => 'nullable|numeric|min:0|max:1',
            'appBackgroundColor' => 'nullable|string|max:9',
            'isMainBorderEnabled' => 'nullable|boolean',
            'mainBorderColor' => 'nullable|string|max:7',
            'showImage' => 'nullable|boolean',
            'imageSize' => 'nullable|integer|min:16|max:512',
            'isPaginationEnabled' => 'nullable|boolean',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'paginationInterval' => 'nullable|integer|min:1|max:60',
            'apiRefreshInterval' => 'nullable|integer|min:5|max:3600',
        ]);

        $config = Configuracao::updateOrCreate(
            ['empresa_id' => $empresa->id],
            $validated
        );

        // ensure we return latest data after save
        $config = $config->fresh();

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Configuração salva com sucesso',
            'dados' => new ConfiguracaoResource($config)
        ], 200);
    }

    /**
     * Update (PUT) the configuration.
     * PUT /api/configuracao
     */
    #[OA\Put(
        path: '/api/configuracoes',
        tags: ['Configurações'],
        summary: 'Atualiza configuração da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Configuração atualizada'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    #[OA\Put(
        path: '/api/configuracao',
        tags: ['Configurações'],
        summary: 'Atualiza configuração da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Configuração atualizada'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function update(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Reset configuration to defaults.
     * DELETE /api/configuracao
     */
    #[OA\Delete(
        path: '/api/configuracoes',
        tags: ['Configurações'],
        summary: 'Operação não suportada para reset automático',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 405, description: 'Método não permitido')
        ]
    )]
    #[OA\Delete(
        path: '/api/configuracao',
        tags: ['Configurações'],
        summary: 'Operação não suportada para reset automático',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 405, description: 'Método não permitido')
        ]
    )]
    public function destroy()
    {
        return response()->json([
            'sucesso' => false,
            'mensagem' => 'Para resetar uma configuração, utilize PUT /api/configuracao com os valores desejados.',
        ], 405);
    }
}
