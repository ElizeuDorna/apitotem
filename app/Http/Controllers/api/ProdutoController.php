<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProdutoResource;
use App\Models\Departamento;
use App\Models\Grupo;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class ProdutoController extends Controller
{
    #[OA\Get(
        path: '/api/produtos',
        tags: ['Produtos'],
        summary: 'Lista produtos da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('empresa');

        $produtos = Produto::query()
            ->with(['departamento:id,nome', 'grupo:id,nome,departamento_id'])
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'produtos' => ProdutoResource::collection($produtos),
            ],
            'meta' => [
                'total_produtos' => $produtos->count(),
            ],
        ], 200);
    }

    #[OA\Post(
        path: '/api/produtos',
        tags: ['Produtos'],
        summary: 'Cria um produto',
        security: [['CompanyBearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/ProdutoPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 201, description: 'Criado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(Request $request)
    {
        $empresa = $request->attributes->get('empresa');

        $validatedData = $request->validate([
            'CODIGO' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('produto', 'CODIGO')->where(fn ($query) => $query->where('empresa_id', $empresa->id)),
            ],
            'NOME' => 'required|string|max:255',
            'PRECO' => 'required|numeric|min:0',
            'OFERTA' => 'nullable|numeric|min:0',
            'IMG' => 'nullable|url|max:500',
            'departamento_id' => 'required|integer|exists:departamentos,id',
            'grupo_id' => 'required|integer|exists:grupos,id',
        ], [
            'CODIGO.unique' => 'Este CÓDIGO já está registrado para esta empresa.',
            'NOME.required' => 'O campo NOME é obrigatório.',
            'PRECO.required' => 'O campo PREÇO é obrigatório.',
        ]);

        $departamento = Departamento::find($validatedData['departamento_id']);
        $grupo = Grupo::find($validatedData['grupo_id']);

        if (! $departamento || ! $grupo) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Departamento ou grupo não encontrado.',
            ], 422);
        }

        if ((int) $departamento->empresa_id !== (int) $empresa->id
            || (int) $grupo->empresa_id !== (int) $empresa->id
            || (int) $grupo->departamento_id !== (int) $departamento->id
        ) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Relacionamento inválido: grupo/departamento fora da empresa autenticada.',
            ], 422);
        }

        $validatedData['empresa_id'] = $empresa->id;

        try {
            $produto = Produto::create($validatedData)->load(['departamento:id,nome', 'grupo:id,nome,departamento_id']);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Produto cadastrado com sucesso',
                'dados' => new ProdutoResource($produto),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Falha ao cadastrar Produto',
                'mensagem' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/produtos/{produto}',
        tags: ['Produtos'],
        summary: 'Busca produto',
        security: [['CompanyBearer' => []]],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function show(Request $request, string $produto)
    {
        $empresa = $request->attributes->get('empresa');

        $produtoModel = Produto::query()
            ->with(['departamento:id,nome', 'grupo:id,nome,departamento_id'])
            ->where('empresa_id', $empresa->id)
            ->where('CODIGO', $produto)
            ->first();

        if (! $produtoModel) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado para a empresa autenticada.',
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => new ProdutoResource($produtoModel),
        ], 200);
    }

    #[OA\Put(
        path: '/api/produtos/{produto}',
        tags: ['Produtos'],
        summary: 'Atualiza um produto',
        security: [['CompanyBearer' => []]],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/ProdutoPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Atualizado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function update(Request $request, string $produto)
    {
        $empresa = $request->attributes->get('empresa');

        $produtoModel = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('CODIGO', $produto)
            ->first();

        if (! $produtoModel) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado para a empresa autenticada.',
            ], 404);
        }

        $validatedData = $request->validate([
            'CODIGO' => [
                'sometimes',
                'nullable',
                'string',
                'max:14',
                Rule::unique('produto', 'CODIGO')
                    ->where(fn ($query) => $query->where('empresa_id', $empresa->id))
                    ->ignore($produtoModel->id),
            ],
            'NOME' => 'sometimes|required|string|max:255',
            'PRECO' => 'sometimes|required|numeric|min:0',
            'OFERTA' => 'nullable|numeric|min:0',
            'IMG' => 'nullable|url|max:500',
            'departamento_id' => 'sometimes|required|integer|exists:departamentos,id',
            'grupo_id' => 'sometimes|required|integer|exists:grupos,id',
        ]);

        $departamentoId = $validatedData['departamento_id'] ?? $produtoModel->departamento_id;
        $grupoId = $validatedData['grupo_id'] ?? $produtoModel->grupo_id;

        $departamento = Departamento::find($departamentoId);
        $grupo = Grupo::find($grupoId);

        if (! $departamento || ! $grupo) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Departamento ou grupo não encontrado.',
            ], 422);
        }

        if ((int) $departamento->empresa_id !== (int) $empresa->id
            || (int) $grupo->empresa_id !== (int) $empresa->id
            || (int) $grupo->departamento_id !== (int) $departamento->id
        ) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Relacionamento inválido: grupo/departamento fora da empresa autenticada.',
            ], 422);
        }

        $validatedData['empresa_id'] = $empresa->id;

        try {
            $produtoModel->update($validatedData);

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Produto atualizado com sucesso',
                'dados' => new ProdutoResource($produtoModel->fresh()->load(['departamento:id,nome', 'grupo:id,nome,departamento_id'])),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Falha ao atualizar Produto',
                'mensagem' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/produtos/{produto}',
        tags: ['Produtos'],
        summary: 'Remove um produto',
        security: [['CompanyBearer' => []]],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Removido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function destroy(Request $request, string $produto)
    {
        $empresa = $request->attributes->get('empresa');

        $produtoModel = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('CODIGO', $produto)
            ->first();

        if (! $produtoModel) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado para a empresa autenticada.',
            ], 404);
        }

        try {
            $codigo = $produtoModel->CODIGO;
            $produtoModel->delete();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Produto deletado com sucesso',
                'CODIGO' => $codigo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro' => 'Falha ao deletar Produto',
                'mensagem' => $e->getMessage(),
            ], 500);
        }
    }
}
