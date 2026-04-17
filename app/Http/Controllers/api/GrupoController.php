<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\GrupoResource;
use App\Models\Departamento;
use App\Models\Grupo;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GrupoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/grupos',
        tags: ['Grupos'],
        summary: 'Lista grupos da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'dados' => [
                        [
                            'id' => 10,
                            'nome' => 'Refrigerantes',
                            'empresa_id' => 1,
                            'departamento_id' => 1,
                            'departamento' => ['id' => 1, 'nome' => 'Bebidas'],
                        ],
                    ],
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('empresa');
        $grupos = Grupo::with('departamento')
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome')
            ->get();

        return response()->json([
            'sucesso' => true,
            'dados' => GrupoResource::collection($grupos)
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/grupos',
        tags: ['Grupos'],
        summary: 'Cria grupo',
        security: [['CompanyBearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/GrupoPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Criado',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'mensagem' => 'Grupo criado com sucesso',
                    'dados' => [
                        'id' => 10,
                        'nome' => 'Refrigerantes',
                        'empresa_id' => 1,
                        'departamento_id' => 1,
                        'departamento' => ['id' => 1, 'nome' => 'Bebidas'],
                    ],
                ])
            ),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(Request $request)
    {
        $empresa = $request->attributes->get('empresa');

        $validated = $request->validate([
            'departamento_id' => 'required|integer|exists:departamentos,id',
            'nome' => 'required|string|max:255'
        ], [
            'departamento_id.required' => 'Departamento é obrigatório.',
            'departamento_id.integer' => 'Departamento deve ser um número inteiro.',
            'departamento_id.exists' => 'Departamento não encontrado.',
            'nome.required' => 'Nome do grupo é obrigatório.'
        ]);

        $departamento = Departamento::findOrFail($validated['departamento_id']);

        if ((int) $departamento->empresa_id !== (int) $empresa->id) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Departamento não pertence à empresa autenticada.',
            ], 422);
        }

        $grupo = Grupo::create([
            'nome' => $validated['nome'],
            'departamento_id' => $validated['departamento_id'],
            'empresa_id' => $empresa->id,
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Grupo criado com sucesso',
            'dados' => new GrupoResource($grupo->load('departamento'))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/grupos/{grupo}',
        tags: ['Grupos'],
        summary: 'Busca grupo',
        security: [['CompanyBearer' => []]],
        parameters: [
            new OA\Parameter(name: 'grupo', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'dados' => [
                        'id' => 10,
                        'nome' => 'Refrigerantes',
                        'empresa_id' => 1,
                        'departamento_id' => 1,
                        'departamento' => ['id' => 1, 'nome' => 'Bebidas'],
                    ],
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function show(Request $request, Grupo $grupo)
    {
        $empresa = $request->attributes->get('empresa');

        if ((int) $grupo->empresa_id !== (int) $empresa->id) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Grupo não encontrado para a empresa autenticada.',
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => new GrupoResource($grupo->load('departamento'))
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Grupo $grupo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/grupos/{grupo}',
        tags: ['Grupos'],
        summary: 'Atualiza grupo',
        security: [['CompanyBearer' => []]],
        parameters: [
            new OA\Parameter(name: 'grupo', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/GrupoPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'mensagem' => 'Grupo atualizado com sucesso',
                    'dados' => [
                        'id' => 10,
                        'nome' => 'Sucos',
                        'empresa_id' => 1,
                        'departamento_id' => 1,
                        'departamento' => ['id' => 1, 'nome' => 'Bebidas'],
                    ],
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function update(Request $request, Grupo $grupo)
    {
        $empresa = $request->attributes->get('empresa');

        if ((int) $grupo->empresa_id !== (int) $empresa->id) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Grupo não encontrado para a empresa autenticada.',
            ], 404);
        }

        $validated = $request->validate([
            'departamento_id' => "sometimes|integer|exists:departamentos,id",
            'nome' => 'sometimes|string|max:255'
        ], [
            'departamento_id.exists' => 'Departamento não encontrado.'
        ]);

        if (isset($validated['departamento_id'])) {
            $departamento = Departamento::findOrFail($validated['departamento_id']);

            if ((int) $departamento->empresa_id !== (int) $empresa->id) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Departamento não pertence à empresa autenticada.',
                ], 422);
            }
        }

        $validated['empresa_id'] = $empresa->id;

        $grupo->update($validated);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Grupo atualizado com sucesso',
            'dados' => new GrupoResource($grupo->load('departamento'))
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/grupos/{grupo}',
        tags: ['Grupos'],
        summary: 'Remove grupo',
        security: [['CompanyBearer' => []]],
        parameters: [
            new OA\Parameter(name: 'grupo', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Removido',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'mensagem' => 'Grupo removido com sucesso',
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function destroy(Request $request, Grupo $grupo)
    {
        $empresa = $request->attributes->get('empresa');

        if ((int) $grupo->empresa_id !== (int) $empresa->id) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Grupo não encontrado para a empresa autenticada.',
            ], 404);
        }

        $grupo->delete();

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Grupo removido com sucesso'
        ]);
    }
}
