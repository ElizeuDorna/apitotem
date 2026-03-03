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
            new OA\Response(response: 200, description: 'OK'),
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
        responses: [
            new OA\Response(response: 201, description: 'Criado'),
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
