<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Rules\CpfCnpjValido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmpresaController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Empresa::orderBy('id', 'desc');

        if (! $user->isDefaultAdmin()) {
            $query->where('cnpj_cpf', $user->documento());
        }

        $empresas = $query->paginate(15);

        return view('admin.empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('admin.empresas.create');
    }

    public function store(Request $request)
    {
        // Remove formatting from cnpj_cpf
        if ($request->filled('cnpj_cpf')) {
            $request->merge([
                'cnpj_cpf' => preg_replace('/\D/', '', (string) $request->input('cnpj_cpf')),
            ]);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'razaosocial' => 'required|string|max:255',
            'cnpj_cpf' => ['required', 'string', 'max:18', 'unique:empresa,cnpj_cpf', new CpfCnpjValido()],
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

        $tentativas = 0;
        while (true) {
            try {
                DB::transaction(function () use ($validated) {
                    Empresa::create($validated);
                });
                break;
            } catch (QueryException $e) {
                $tentativas++;
                if ($tentativas >= 3 || (int) $e->getCode() !== 23000) {
                    throw $e;
                }
            }
        }

        return redirect()->route('admin.empresas.index')->with('success', 'Empresa criada com sucesso');
    }

    public function edit(Empresa $empresa)
    {
        $this->authorizeEmpresaAccess($empresa);

        return view('admin.empresas.edit', compact('empresa'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $this->authorizeEmpresaAccess($empresa);

        // Remove formatting from cnpj_cpf
        if ($request->filled('cnpj_cpf')) {
            $request->merge([
                'cnpj_cpf' => preg_replace('/\D/', '', (string) $request->input('cnpj_cpf')),
            ]);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'razaosocial' => 'required|string|max:255',
            'cnpj_cpf' => ['required', 'string', 'max:18', 'unique:empresa,cnpj_cpf,' . $empresa->id, new CpfCnpjValido()],
            'email' => 'required|email|max:255|unique:empresa,email,' . $empresa->id,
            'fone' => 'required|string|max:20',
            'password' => 'nullable|string|min:6|max:60',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
        ]);

        if (! Auth::user()->isDefaultAdmin()) {
            $validated['cnpj_cpf'] = Auth::user()->documento();
        }

        $validated['fantasia'] = $validated['nome'];
        if (empty($empresa->urlimagem)) {
            $validated['urlimagem'] = '';
        }

        if ($validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $empresa->update($validated);

        return redirect()->route('admin.empresas.index')->with('success', 'Empresa atualizada com sucesso');
    }

    public function destroy(Empresa $empresa)
    {
        $this->authorizeEmpresaAccess($empresa);

        $nome = $empresa->nome;

        $empresa->delete();

        return redirect()->route('admin.empresas.index')->with('success', "Empresa {$nome} deletada com sucesso");
    }

    private function authorizeEmpresaAccess(Empresa $empresa): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        abort_unless($empresa->cnpj_cpf === $user->documento(), 403);
    }
}
