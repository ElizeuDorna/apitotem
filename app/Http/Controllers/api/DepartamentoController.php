<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DepartamentoResource;
use App\Models\Departamento;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/departamentos',
        tags: ['Departamentos'],
        summary: 'Lista departamentos da empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('empresa');
        $departamentos = Departamento::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome')
            ->get();

        return response()->json([
            'sucesso' => true,
            'dados' => DepartamentoResource::collection($departamentos)
        ], 200);
    }

    #[OA\Post(
        path: '/api/departamentos',
        tags: ['Departamentos'],
        summary: 'Cria departamento',
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
            'nome' => 'required|string|max:255'
        ], [
            'nome.required' => 'Nome do departamento é obrigatório.'
        ]);

        $departamento = Departamento::create([
            'nome' => $validated['nome'],
            'empresa_id' => $empresa->id,
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Departamento criado com sucesso',
            'dados' => new DepartamentoResource($departamento)
        ], 201);
    }

    public function show(Request $request, Departamento $departamento)
    {
        $empresa = $request->attributes->get('empresa');

        if ((int) $departamento->empresa_id !== (int) $empresa->id) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Departamento não encontrado para a empresa autenticada.',
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'dados' => new DepartamentoResource($departamento)
        ], 200);
    }

    public function update(Request $request, Departamento $departamento)
    {
        $empresa = $request->attributes->get('empresa');

        if ((int) $departamento->empresa_id !== (int) $empresa->id) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Departamento não encontrado para a empresa autenticada.',
            ], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255'
        ], [
            'nome.required' => 'Nome do departamento é obrigatório.'
        ]);

        $departamento->update([
            'nome' => $validated['nome'] ?? $departamento->nome,
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Departamento atualizado com sucesso',
            'dados' => new DepartamentoResource($departamento)
        ], 200);
    }

    public function destroy(Request $request, Departamento $departamento)
    {
        $empresa = $request->attributes->get('empresa');

        if ((int) $departamento->empresa_id !== (int) $empresa->id) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Departamento não encontrado para a empresa autenticada.',
            ], 404);
        }

        $departamento->delete();
        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Departamento removido'
        ], 200);
    }
}
