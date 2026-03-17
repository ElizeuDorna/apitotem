<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\Departamento;
use App\Models\Grupo;
use App\Support\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && EmpresaContext::requiresSelection($user) && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para continuar.');
        }

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $query = Produto::with(['departamento', 'grupo']);

        if ($this->usesEmpresaSegregation() && $empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $produtos = $query->paginate(15)->appends($request->query());
        $departamentos = $this->departamentosQueryForUser($user)->get();
        $grupos = $this->gruposQueryForUser($user)->get();
        return view('admin.produtos.index', compact('produtos', 'departamentos', 'grupos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && EmpresaContext::requiresSelection($user) && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar produto.');
        }

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar produto.');
        }

        $departamentos = $this->departamentosQueryForUser($user)
            ->when(
                $empresaId,
                fn ($query) => $query->where('empresa_id', $empresaId)
            )
            ->get();

        $grupos = $this->gruposQueryForUser($user)
            ->when(
                $empresaId,
                fn ($query) => $query->where('empresa_id', $empresaId)
            )
            ->get();

        $empresaAtiva = EmpresaContext::activeEmpresa($user);
        $cnpjCpfEmpresa = (string) ($empresaAtiva?->cnpj_cpf ?? $user->documento());

        if ($empresaId) {
            $cnpjCpfEmpresa = (string) (Empresa::query()->find($empresaId)?->cnpj_cpf ?? $cnpjCpfEmpresa);
        }

        return view('admin.produtos.create', compact('departamentos', 'grupos', 'cnpjCpfEmpresa'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $empresaIdUsuario = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && EmpresaContext::requiresSelection($user) && ! $empresaIdUsuario) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar produto.');
        }

        if ($user->isDefaultAdmin() && ! $empresaIdUsuario) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar produto.');
        }

        $departamentoId = (int) $request->input('departamento_id');
        $departamentoForUnique = $departamentoId > 0 ? Departamento::find($departamentoId) : null;
        $empresaIdForUnique = $empresaIdUsuario;

        $validated = $request->validate([
            'CODIGO' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('produto', 'CODIGO')->where(function ($query) use ($empresaIdForUnique) {
                    if ($empresaIdForUnique !== null) {
                        return $query->where('empresa_id', $empresaIdForUnique);
                    }

                    return $query->whereRaw('1 = 0');
                }),
            ],
            'NOME' => 'required|string|max:255',
            'cnpj_cpf' => 'nullable|string|max:18',
            'PRECO' => 'required|numeric|min:0',
            'OFERTA' => 'nullable|numeric|min:0',
            'IMG' => 'nullable|url|max:500',
            'departamento_id' => 'required|exists:departamentos,id',
            'grupo_id' => 'required|exists:grupos,id',
        ], [
            'CODIGO.unique' => 'Este código já existe para esta empresa.',
        ]);

        // Validate that grupo belongs to departamento
        $grupo = Grupo::find($validated['grupo_id']);
        if ($grupo->departamento_id != $validated['departamento_id']) {
            return back()->withErrors(['grupo_id' => 'Grupo deve pertencer ao departamento selecionado']);
        }

        $departamento = Departamento::find($validated['departamento_id']);

        if (! $departamento) {
            abort(403);
        }

        $empresaId = $user->isDefaultAdmin() ? $empresaIdUsuario : $empresaIdUsuario;

        if ($user->isDefaultAdmin()) {
            if ((int) $departamento->empresa_id !== (int) $empresaIdUsuario || (int) $grupo->empresa_id !== (int) $empresaIdUsuario) {
                abort(403, 'Departamento/Grupo fora da empresa ativa selecionada.');
            }
        }

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation()) {
            if (! $departamento || $departamento->empresa_id !== $empresaIdUsuario || $grupo->empresa_id !== $empresaIdUsuario) {
                abort(403);
            }
        }

        $empresaDocumento = (string) ($validated['cnpj_cpf'] ?? $user->documento());

        if ($empresaId !== null) {
            $empresaDocumento = (string) (Empresa::query()->find($empresaId)?->cnpj_cpf ?? $empresaDocumento);
        }

        $validated['cnpj_cpf'] = preg_replace('/\D/', '', $empresaDocumento);
        $validated['OFERTA'] = isset($validated['OFERTA']) && $validated['OFERTA'] !== ''
            ? (float) $validated['OFERTA']
            : 0;

        $validated['empresa_id'] = $empresaId;

        Produto::create($validated);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto criado com sucesso');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Produto $produto)
    {
        $this->authorizeProdutoAccess($produto);

        $user = Auth::user();

        $departamentos = $this->departamentosQueryForUser($user)->get();
        $grupos = $this->gruposQueryForUser($user)->get();
        return view('admin.produtos.edit', compact('produto', 'departamentos', 'grupos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Produto $produto)
    {
        $user = Auth::user();
        $empresaIdUsuario = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && EmpresaContext::requiresSelection($user) && ! $empresaIdUsuario) {
            abort(403, 'Selecione uma empresa ativa em Empresas para atualizar produto.');
        }

        if ($user->isDefaultAdmin() && ! $empresaIdUsuario) {
            abort(403, 'Selecione uma empresa ativa em Empresas para atualizar produto.');
        }

        $this->authorizeProdutoAccess($produto);

        $departamentoId = (int) $request->input('departamento_id', $produto->departamento_id);
        $departamentoForUnique = $departamentoId > 0 ? Departamento::find($departamentoId) : null;
        $empresaIdForUnique = $empresaIdUsuario;

        $validated = $request->validate([
            'CODIGO' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('produto', 'CODIGO')
                    ->where(function ($query) use ($empresaIdForUnique) {
                        if ($empresaIdForUnique !== null) {
                            return $query->where('empresa_id', $empresaIdForUnique);
                        }

                        return $query->whereRaw('1 = 0');
                    })
                    ->ignore($produto->id),
            ],
            'NOME' => 'required|string|max:255',
            'cnpj_cpf' => 'required|string|max:18',
            'PRECO' => 'required|numeric|min:0',
            'OFERTA' => 'nullable|numeric|min:0',
            'IMG' => 'nullable|url|max:500',
            'departamento_id' => 'required|exists:departamentos,id',
            'grupo_id' => 'required|exists:grupos,id',
        ], [
            'CODIGO.unique' => 'Este código já existe para esta empresa.',
        ]);

        // Validate that grupo belongs to departamento
        $grupo = Grupo::find($validated['grupo_id']);
        if ($grupo->departamento_id != $validated['departamento_id']) {
            return back()->withErrors(['grupo_id' => 'Grupo deve pertencer ao departamento selecionado']);
        }

        $departamento = Departamento::find($validated['departamento_id']);

        if (! $departamento) {
            abort(403);
        }

        $empresaId = $empresaIdUsuario;

        if ($user->isDefaultAdmin()) {
            if ((int) $departamento->empresa_id !== (int) $empresaIdUsuario || (int) $grupo->empresa_id !== (int) $empresaIdUsuario) {
                abort(403, 'Departamento/Grupo fora da empresa ativa selecionada.');
            }
        }

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation()) {
            if (! $departamento || $departamento->empresa_id !== $empresaIdUsuario || $grupo->empresa_id !== $empresaIdUsuario) {
                abort(403);
            }
        }

        $validated['cnpj_cpf'] = preg_replace('/\D/', '', (string) $validated['cnpj_cpf']);
        $validated['OFERTA'] = isset($validated['OFERTA']) && $validated['OFERTA'] !== ''
            ? (float) $validated['OFERTA']
            : 0;

        $validated['empresa_id'] = $empresaId;

        $produto->update($validated);

        return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado com sucesso');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Produto $produto)
    {
        $this->authorizeProdutoAccess($produto);

        $codigo = $produto->CODIGO;
        $produto->delete();

        return redirect()->route('admin.produtos.index')->with('success', "Produto {$codigo} deletado com sucesso");
    }

    private function authorizeProdutoAccess(Produto $produto): void
    {
        $user = Auth::user();
        $empresaIdUsuario = EmpresaContext::resolveEmpresaIdForUser($user);

        if ($user->isDefaultAdmin()) {
            if ($empresaIdUsuario) {
                abort_unless((int) $produto->empresa_id === (int) $empresaIdUsuario, 403);
            }
            return;
        }

        if (! $this->usesEmpresaSegregation()) {
            return;
        }

        $produto->loadMissing(['departamento:id,empresa_id', 'grupo:id,empresa_id']);

        $empresaId = $produto->empresa_id ?? $produto->departamento?->empresa_id ?? $produto->grupo?->empresa_id;

        if ($empresaId !== null) {
            abort_unless((int) $empresaIdUsuario === (int) $empresaId, 403);
            return;
        }

        abort(403);
    }

    private function usesEmpresaSegregation(): bool
    {
        return Schema::hasColumn('departamentos', 'empresa_id')
            && Schema::hasColumn('grupos', 'empresa_id')
            && Schema::hasColumn('produto', 'empresa_id');
    }

    private function departamentosQueryForUser($user)
    {
        return Departamento::query()
            ->when(
                ! $user->isDefaultAdmin() && $this->usesEmpresaSegregation(),
                fn ($query) => $query->where('empresa_id', EmpresaContext::resolveEmpresaIdForUser($user))
            );
    }

    private function gruposQueryForUser($user)
    {
        return Grupo::query()
            ->when(
                ! $user->isDefaultAdmin() && $this->usesEmpresaSegregation(),
                fn ($query) => $query->where('empresa_id', EmpresaContext::resolveEmpresaIdForUser($user))
            );
    }
}
