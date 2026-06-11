<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\User;
use App\Rules\CpfCnpjValido;
use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmpresaService
{
    public function __construct(private readonly EmpresaOnboardingService $onboardingService) {}

    public function canSearch(User $user): bool
    {
        $empresaVinculada = EmpresaContext::resolveEmpresaForUser($user);

        return $user->isDefaultAdmin() || ($empresaVinculada && $empresaVinculada->isRevenda());
    }

    public function queryForUser(User $user, string $search = ''): Builder
    {
        $empresaVinculada = EmpresaContext::resolveEmpresaForUser($user);

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

        $search = trim($search);

        if ($this->canSearch($user) && $search !== '') {
            $digitsBusca = preg_replace('/\D/', '', $search);

            $query->where(function ($empresaQuery) use ($search, $digitsBusca) {
                $empresaQuery->where('nome', 'like', "%{$search}%")
                    ->orWhere('razaosocial', 'like', "%{$search}%");

                if ($digitsBusca !== '') {
                    $empresaQuery->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cnpj_cpf, '.', ''), '/', ''), '-', ''), '(', ''), ')', '') like ?",
                        ["%{$digitsBusca}%"]
                    );
                } else {
                    $empresaQuery->orWhere('cnpj_cpf', 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }

    public function availableRevendas(User $user): Collection
    {
        if (! $user->isDefaultAdmin()) {
            $empresaVinculada = EmpresaContext::resolveEmpresaForUser($user);
            abort_unless($empresaVinculada && $empresaVinculada->isRevenda(), 403);
        }

        return Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_REVENDA)
            ->orderBy('nome')
            ->get(['id', 'nome', 'fantasia']);
    }

    public function activeEmpresaSummary(User $user): array
    {
        $empresaAtivaId = EmpresaContext::resolveEmpresaIdForUser($user);
        $empresaAtivaNome = null;

        if ($empresaAtivaId) {
            $empresaAtivaNome = Empresa::query()->where('id', $empresaAtivaId)->value('nome');
        }

        return [$empresaAtivaId, $empresaAtivaNome];
    }

    public function createForUser(User $user, array $data): Empresa
    {
        $empresaVinculada = EmpresaContext::resolveEmpresaForUser($user);

        $validated = validator($data, [
            'nome' => ['required', 'string', 'max:255'],
            'razaosocial' => ['required', 'string', 'max:255'],
            'cnpj_cpf' => ['required', 'string', 'max:18', Rule::unique('empresa', 'cnpj_cpf'), new CpfCnpjValido()],
            'email' => ['required', 'email', 'max:255', Rule::unique('empresa', 'email')],
            'fone' => ['required', 'string', 'max:20'],
            'senha_integracao_api' => ['nullable', 'string', 'min:6', 'max:120'],
            'nivel_acesso' => ['nullable', 'integer', 'in:1,2'],
            'revenda_id' => ['nullable', 'integer', 'exists:empresa,id'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'numero' => ['nullable', 'string', 'max:20'],
            'cep' => ['nullable', 'string', 'max:10'],
            'public_page_enabled' => ['nullable', 'boolean'],
            'public_page_slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('empresa', 'public_page_slug')],
        ], [
            'public_page_slug.regex' => 'Use apenas letras minusculas, numeros e hifens no slug publico.',
        ])->validate();

        $validated['cnpj_cpf'] = preg_replace('/\D/', '', (string) $validated['cnpj_cpf']);
        $validated['public_page_enabled'] = (bool) ($validated['public_page_enabled'] ?? false);

        if ($validated['public_page_enabled'] && (int) ($validated['nivel_acesso'] ?? Empresa::NIVEL_CLIENTE_FINAL) === Empresa::NIVEL_REVENDA && empty($validated['public_page_slug']) && ! empty($validated['nome'])) {
            $validated['public_page_slug'] = Str::slug((string) $validated['nome']);
        }

        if (! empty($validated['senha_integracao_api'])) {
            $validated['senha_integracao_api'] = Hash::make($validated['senha_integracao_api']);
        } else {
            unset($validated['senha_integracao_api']);
        }

        if ($user->isDefaultAdmin()) {
            $validated['nivel_acesso'] = (int) ($validated['nivel_acesso'] ?? Empresa::NIVEL_CLIENTE_FINAL);
            $validated['cadastro_origem'] = Empresa::CADASTRO_ORIGEM_ADMIN;
        } else {
            abort_unless($empresaVinculada && $empresaVinculada->isRevenda(), 403);
            $validated['nivel_acesso'] = Empresa::NIVEL_CLIENTE_FINAL;
            $validated['revenda_id'] = $empresaVinculada->id;
            $validated['cadastro_origem'] = Empresa::CADASTRO_ORIGEM_REVENDA;
        }

        if ((int) $validated['nivel_acesso'] === Empresa::NIVEL_REVENDA) {
            $validated['revenda_id'] = null;
        } elseif (! empty($validated['revenda_id'])) {
            $revenda = Empresa::query()->find($validated['revenda_id']);
            if (! $revenda || ! $revenda->isRevenda()) {
                throw ValidationException::withMessages([
                    'revenda_id' => 'Selecione uma revenda valida.',
                ]);
            }
        }

        if ((int) $validated['nivel_acesso'] !== Empresa::NIVEL_REVENDA) {
            $validated['public_page_enabled'] = false;
            $validated['public_page_slug'] = null;
        }

        $validated['fantasia'] = $validated['nome'];
        $validated['urlimagem'] = '';

        $tentativas = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($validated) {
                    $empresa = Empresa::query()->create($validated);
                    $this->onboardingService->provisionManagedClient($empresa);

                    return $empresa;
                });
            } catch (QueryException $e) {
                $tentativas++;
                if ($tentativas >= 3 || (int) $e->getCode() !== 23000) {
                    throw $e;
                }
            }
        }
    }

    public function updateForUser(User $user, Empresa $empresa, array $data): Empresa
    {
        $this->authorizeEmpresaAccess($user, $empresa);

        $validated = validator($data, [
            'nome' => ['required', 'string', 'max:255'],
            'razaosocial' => ['required', 'string', 'max:255'],
            'cnpj_cpf' => ['required', 'string', 'max:18', Rule::unique('empresa', 'cnpj_cpf')->ignore($empresa->id), new CpfCnpjValido()],
            'email' => ['required', 'email', 'max:255', Rule::unique('empresa', 'email')->ignore($empresa->id)],
            'fone' => ['required', 'string', 'max:20'],
            'senha_integracao_api' => ['nullable', 'string', 'min:6', 'max:120'],
            'nivel_acesso' => ['nullable', 'integer', 'in:1,2'],
            'revenda_id' => ['nullable', 'integer', 'exists:empresa,id'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'numero' => ['nullable', 'string', 'max:20'],
            'cep' => ['nullable', 'string', 'max:10'],
            'public_page_enabled' => ['nullable', 'boolean'],
            'public_page_slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('empresa', 'public_page_slug')->ignore($empresa->id)],
        ], [
            'public_page_slug.regex' => 'Use apenas letras minusculas, numeros e hifens no slug publico.',
        ])->validate();

        $validated['cnpj_cpf'] = preg_replace('/\D/', '', (string) $validated['cnpj_cpf']);
        $validated['public_page_enabled'] = (bool) ($validated['public_page_enabled'] ?? false);

        if ($validated['public_page_enabled'] && (int) ($validated['nivel_acesso'] ?? $empresa->nivel_acesso ?? Empresa::NIVEL_CLIENTE_FINAL) === Empresa::NIVEL_REVENDA && empty($validated['public_page_slug']) && ! empty($validated['nome'])) {
            $validated['public_page_slug'] = Str::slug((string) $validated['nome']);
        }

        if (! empty($validated['senha_integracao_api'])) {
            $validated['senha_integracao_api'] = Hash::make($validated['senha_integracao_api']);
        } else {
            unset($validated['senha_integracao_api']);
        }

        $empresaVinculada = EmpresaContext::resolveEmpresaForUser($user);

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
                throw ValidationException::withMessages([
                    'revenda_id' => 'A empresa nao pode ser vinculada a si mesma como revenda.',
                ]);
            }

            $revenda = Empresa::query()->find($validated['revenda_id']);
            if (! $revenda || ! $revenda->isRevenda()) {
                throw ValidationException::withMessages([
                    'revenda_id' => 'Selecione uma revenda valida.',
                ]);
            }
        }

        if ((int) $validated['nivel_acesso'] !== Empresa::NIVEL_REVENDA) {
            $validated['public_page_enabled'] = false;
            $validated['public_page_slug'] = null;
        }

        $validated['fantasia'] = $validated['nome'];
        if (empty($empresa->urlimagem)) {
            $validated['urlimagem'] = '';
        }

        $empresa->update($validated);

        return $empresa->refresh();
    }

    public function deleteForUser(User $user, Empresa $empresa): void
    {
        $this->authorizeEmpresaAccess($user, $empresa);
        $empresa->delete();
    }

    public function authorizeEmpresaAccess(User $user, Empresa $empresa): void
    {
        if ($user->isDefaultAdmin()) {
            return;
        }

        $empresaVinculada = EmpresaContext::resolveEmpresaForUser($user);

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