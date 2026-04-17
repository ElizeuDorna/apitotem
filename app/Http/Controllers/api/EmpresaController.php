<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller; 
use App\Models\Empresa;
use App\Rules\CpfCnpjValido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use OpenApi\Attributes as OA;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/empresas',
        tags: ['Empresas'],
        summary: 'Lista empresas',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'mensagem' => 'Empresas listadas com sucesso',
                    'dados' => [
                        [
                            'id' => 1,
                            'codigo' => '1',
                            'nome' => 'Mercado Exemplo',
                            'razaosocial' => 'Mercado Exemplo LTDA',
                            'cnpj_cpf' => '12345678000199',
                            'email' => 'contato@mercadoexemplo.com',
                            'fone' => '11999998888',
                            'endereco' => 'Rua Central',
                            'bairro' => 'Centro',
                            'numero' => '100',
                            'cep' => '01001000',
                            'fantasia' => 'Mercado Exemplo',
                            'urlimagem' => '',
                        ],
                    ],
                ])
            ),
        ]
    )]
    public function index()
    {
        $empresas = Empresa::all();
        
        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Empresas listadas com sucesso',
            'dados' => $empresas
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/empresas',
        tags: ['Empresas'],
        summary: 'Cria empresa',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/EmpresaPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Criado',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'mensagem' => 'Empresa cadastrada com sucesso',
                    'dados' => [
                        'id' => 1,
                        'codigo' => '1',
                        'nome' => 'Mercado Exemplo',
                        'razaosocial' => 'Mercado Exemplo LTDA',
                        'cnpj_cpf' => '12345678000199',
                        'email' => 'contato@mercadoexemplo.com',
                        'fone' => '11999998888',
                        'endereco' => 'Rua Central',
                        'bairro' => 'Centro',
                        'numero' => '100',
                        'cep' => '01001000',
                        'fantasia' => 'Mercado Exemplo',
                        'urlimagem' => '',
                    ],
                ])
            ),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(Request $request)
    {
        if ($request->filled('cnpj_cpf')) {
            $request->merge([
                'cnpj_cpf' => preg_replace('/\D/', '', (string) $request->input('cnpj_cpf')),
            ]);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'razaosocial' => 'required|string|max:255',
            'cnpj_cpf' => ['required', 'string', 'max:14', 'unique:empresa,cnpj_cpf', new CpfCnpjValido()],
            'email' => 'required|email|max:255|unique:empresa,email',
            'fone' => 'required|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
        ]);

        $validated['fantasia'] = $validated['nome'];
        $validated['urlimagem'] = '';

        try {
            $tentativas = 0;
            while (true) {
                try {
                    $empresa = DB::transaction(function () use ($validated) {
                        return Empresa::create($validated);
                    });
                    break;
                } catch (QueryException $e) {
                    $tentativas++;
                    if ($tentativas >= 3 || (int) $e->getCode() !== 23000) {
                        throw $e;
                    }
                }
            }

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Empresa cadastrada com sucesso',
                'dados' => $empresa
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Falha ao cadastrar Empresa',
                'dados' => null,
                'erro' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/empresas/{id}',
        tags: ['Empresas'],
        summary: 'Atualiza empresa',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/EmpresaPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'mensagem' => 'Empresa atualizada com sucesso',
                    'dados' => [
                        'id' => 1,
                        'codigo' => '1',
                        'nome' => 'Mercado Exemplo Atualizado',
                        'razaosocial' => 'Mercado Exemplo LTDA',
                        'cnpj_cpf' => '12345678000199',
                        'email' => 'contato@mercadoexemplo.com',
                        'fone' => '11999998888',
                        'endereco' => 'Rua Central',
                        'bairro' => 'Centro',
                        'numero' => '100',
                        'cep' => '01001000',
                        'fantasia' => 'Mercado Exemplo Atualizado',
                        'urlimagem' => '',
                    ],
                ])
            ),
            new OA\Response(response: 404, description: 'Não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function update(Request $request, string $id)
    {
        $empresa = Empresa::find($id);

        if (!$empresa) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Empresa não encontrada',
                'dados' => null
            ], 404);
        }

        if ($request->filled('cnpj_cpf')) {
            $request->merge([
                'cnpj_cpf' => preg_replace('/\D/', '', (string) $request->input('cnpj_cpf')),
            ]);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'razaosocial' => 'sometimes|required|string|max:255',
            'cnpj_cpf' => ['sometimes', 'required', 'string', 'max:14', 'unique:empresa,cnpj_cpf,' . $empresa->id, new CpfCnpjValido()],
            'email' => 'sometimes|required|email|max:255|unique:empresa,email,' . $empresa->id,
            'fone' => 'sometimes|required|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
        ]);

        if (array_key_exists('nome', $validated)) {
            $validated['fantasia'] = $validated['nome'];
        }
        if (empty($empresa->urlimagem)) {
            $validated['urlimagem'] = '';
        }

        $empresa->update($validated);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Empresa atualizada com sucesso',
            'dados' => $empresa->fresh()
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/empresas/{id}',
        tags: ['Empresas'],
        summary: 'Remove empresa',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Removido',
                content: new OA\JsonContent(example: [
                    'sucesso' => true,
                    'mensagem' => 'Empresa deletada com sucesso',
                    'dados' => null,
                ])
            ),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function destroy(string $id)
    {
        $empresa = Empresa::find($id);

        if (!$empresa) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Empresa não encontrada',
                'dados' => null
            ], 404);
        }

        $empresa->delete();

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Empresa deletada com sucesso',
            'dados' => null
        ], 200);
    }
}
