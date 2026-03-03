<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller; 
use App\Models\Empresa;
use App\Rules\CpfCnpjValido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
            'password' => 'required|string|min:6|max:60',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
        ]);

        $validated['fantasia'] = $validated['nome'];
        $validated['urlimagem'] = '';
        $validated['password'] = Hash::make($validated['password']);

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
            'password' => 'sometimes|nullable|string|min:6|max:60',
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
        if (isset($validated['password']) && $validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
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
