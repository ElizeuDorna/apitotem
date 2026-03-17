<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Rules\CpfCnpjValido;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $buscaEmpresa = trim((string) $request->query('q', ''));
        $empresaVinculada = $user->empresa;
        $podePesquisar = $user->isDefaultAdmin() || ($empresaVinculada && $empresaVinculada->isRevenda());

        $query = Empresa::query()
            ->with('revenda:id,nome,fantasia')
            ->orderBy('id', 'desc');

        if (! $user->isDefaultAdmin()) {
            if (! $empresaVinculada) {
                if ($user->documento() !== '') {
                    $query->where('cnpj_cpf', $user->documento());
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif ($empresaVinculada->isRevenda()) {
                $query->where('revenda_id', $empresaVinculada->id);
            } else {
                $query->where('id', $empresaVinculada->id);
            }
        }

        if ($podePesquisar && $buscaEmpresa !== '') {
            $digitsBusca = preg_replace('/\D/', '', $buscaEmpresa);

            $query->where(function ($empresaQuery) use ($buscaEmpresa, $digitsBusca) {
                $empresaQuery->where('nome', 'like', "%{$buscaEmpresa}%")
                    ->orWhere('razaosocial', 'like', "%{$buscaEmpresa}%");

                if ($digitsBusca !== '') {
                    $empresaQuery->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cnpj_cpf, '.', ''), '/', ''), '-', ''), '(', ''), ')', '') like ?",
                        ["%{$digitsBusca}%"]
                    );
                } else {
                    $empresaQuery->orWhere('cnpj_cpf', 'like', "%{$buscaEmpresa}%");
                }
            });
        }

        $empresas = $query->paginate(15)->appends($request->query());

        $empresaAtivaId = EmpresaContext::resolveEmpresaIdForUser($user);
        $empresaAtivaNome = null;

        if ($empresaAtivaId) {
            $empresaAtivaNome = Empresa::query()->where('id', $empresaAtivaId)->value('nome');
        }

        return view('admin.empresas.index', compact('empresas', 'buscaEmpresa', 'empresaAtivaId', 'empresaAtivaNome', 'podePesquisar'));
    }

    public function selecionarEmpresaAtiva(Request $request, Empresa $empresa): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($user->isDefaultAdmin() || EmpresaContext::requiresSelection($user), 403);
        abort_unless(EmpresaContext::setActiveEmpresa($user, $empresa), 403);

        return redirect()
            ->route('admin.empresas.index', $request->query())
            ->with('success', 'Empresa ativa selecionada com sucesso.');
    }

    public function limparEmpresaAtiva(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless($user->isDefaultAdmin() || EmpresaContext::requiresSelection($user), 403);

        if ($user->isDefaultAdmin()) {
            session()->forget(EmpresaContext::ADMIN_SESSION_KEY);
        } else {
            session()->forget(EmpresaContext::SESSION_KEY);
        }

        return redirect()
            ->route('admin.empresas.index', $request->query())
            ->with('success', 'Empresa ativa removida.');
    }

    public function create()
    {
        $user = Auth::user();
        $empresaVinculada = $user->empresa;

        if (! $user->isDefaultAdmin()) {
            abort_unless($empresaVinculada && $empresaVinculada->isRevenda(), 403);
        }

        $revendas = Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_REVENDA)
            ->orderBy('nome')
            ->get(['id', 'nome', 'fantasia']);

        return view('admin.empresas.create', [
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'empresaVinculada' => $empresaVinculada,
            'revendas' => $revendas,
        ]);
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
            'nivel_acesso' => 'nullable|integer|in:1,2',
            'revenda_id' => 'nullable|integer|exists:empresa,id',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
        ]);

        $user = Auth::user();
        $empresaVinculada = $user->empresa;

        if ($user->isDefaultAdmin()) {
            $validated['nivel_acesso'] = (int) ($validated['nivel_acesso'] ?? Empresa::NIVEL_CLIENTE_FINAL);
        } else {
            abort_unless($empresaVinculada && $empresaVinculada->isRevenda(), 403);
            $validated['nivel_acesso'] = Empresa::NIVEL_CLIENTE_FINAL;
            $validated['revenda_id'] = $empresaVinculada->id;
        }

        if ((int) $validated['nivel_acesso'] === Empresa::NIVEL_REVENDA) {
            $validated['revenda_id'] = null;
        } elseif (! empty($validated['revenda_id'])) {
            $revenda = Empresa::query()->find($validated['revenda_id']);
            if (! $revenda || ! $revenda->isRevenda()) {
                return back()
                    ->withErrors(['revenda_id' => 'Selecione uma revenda valida.'])
                    ->withInput();
            }
        }

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

        $user = Auth::user();

        $revendas = Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_REVENDA)
            ->orderBy('nome')
            ->get(['id', 'nome', 'fantasia']);

        return view('admin.empresas.edit', [
            'empresa' => $empresa,
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'revendas' => $revendas,
        ]);
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
            'nivel_acesso' => 'nullable|integer|in:1,2',
            'revenda_id' => 'nullable|integer|exists:empresa,id',
            'endereco' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:100',
            'numero' => 'nullable|string|max:20',
            'cep' => 'nullable|string|max:10',
        ]);

        $user = Auth::user();
        $empresaVinculada = $user->empresa;

        if ($user->isDefaultAdmin()) {
            $validated['nivel_acesso'] = (int) ($validated['nivel_acesso'] ?? $empresa->nivel_acesso ?? Empresa::NIVEL_CLIENTE_FINAL);
        } else {
            abort_unless($empresaVinculada && $empresaVinculada->isRevenda(), 403);
            $validated['nivel_acesso'] = Empresa::NIVEL_CLIENTE_FINAL;
            $validated['revenda_id'] = $empresaVinculada->id;
            if ((int) $empresa->id === (int) $empresaVinculada->id) {
                $validated['nivel_acesso'] = Empresa::NIVEL_REVENDA;
                $validated['revenda_id'] = null;
            }
        }

        if ((int) $validated['nivel_acesso'] === Empresa::NIVEL_REVENDA) {
            $validated['revenda_id'] = null;
        } elseif (! empty($validated['revenda_id'])) {
            if ((int) $validated['revenda_id'] === (int) $empresa->id) {
                return back()
                    ->withErrors(['revenda_id' => 'A empresa nao pode ser vinculada a si mesma como revenda.'])
                    ->withInput();
            }

            $revenda = Empresa::query()->find($validated['revenda_id']);
            if (! $revenda || ! $revenda->isRevenda()) {
                return back()
                    ->withErrors(['revenda_id' => 'Selecione uma revenda valida.'])
                    ->withInput();
            }
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

        $empresaVinculada = $user->empresa;

        if (! $empresaVinculada) {
            abort_unless($empresa->cnpj_cpf === $user->documento(), 403);
            return;
        }

        if ($empresaVinculada->isRevenda()) {
            $isEmpresaDaRevenda = (int) $empresa->id === (int) $empresaVinculada->id
                || (int) $empresa->revenda_id === (int) $empresaVinculada->id;

            abort_unless($isEmpresaDaRevenda, 403);
            return;
        }

        abort_unless((int) $empresa->id === (int) $empresaVinculada->id, 403);
    }
}
