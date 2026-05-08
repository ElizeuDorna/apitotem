<?php

namespace App\Services;

use App\Models\Configuracao;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\Produto;
use App\Models\User;
use App\Support\EmpresaContext;
use App\Support\ImageStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProdutoService
{
    public function ensureIndexAccess(User $user): void
    {
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && EmpresaContext::requiresSelection($user) && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para continuar.');
        }

        if (! $user->isDefaultAdmin() && $this->usesEmpresaSegregation() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }
    }

    public function productsQueryForUser(User $user): Builder
    {
        $query = Produto::query()->with(['departamento', 'grupo']);
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if ($this->usesEmpresaSegregation() && $empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        return $query;
    }

    public function departmentsQueryForUser(User $user): Builder
    {
        return Departamento::query()
            ->when(
                ! $user->isDefaultAdmin() && $this->usesEmpresaSegregation(),
                fn ($query) => $query->where('empresa_id', EmpresaContext::resolveEmpresaIdForUser($user))
            );
    }

    public function groupsQueryForUser(User $user): Builder
    {
        return Grupo::query()
            ->when(
                ! $user->isDefaultAdmin() && $this->usesEmpresaSegregation(),
                fn ($query) => $query->where('empresa_id', EmpresaContext::resolveEmpresaIdForUser($user))
            );
    }

    public function createForUser(User $user, array $data): Produto
    {
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && EmpresaContext::requiresSelection($user) && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar produto.');
        }

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar produto.');
        }

        $payload = $this->validateAndNormalize($empresaId, $data);

        return Produto::query()->create($payload);
    }

    public function updateForUser(User $user, Produto $produto, array $data): Produto
    {
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && EmpresaContext::requiresSelection($user) && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para atualizar produto.');
        }

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para atualizar produto.');
        }

        $this->authorizeProdutoAccess($user, $produto);

        $payload = $this->validateAndNormalize($empresaId, $data, $produto);

        $produto->update($payload);

        return $produto->refresh();
    }

    public function deleteForUser(User $user, Produto $produto): void
    {
        $this->authorizeProdutoAccess($user, $produto);
        $produto->delete();
    }

    public function authorizeProdutoAccess(User $user, Produto $produto): void
    {
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

    public function resolvePreviewSize(?int $empresaId): int
    {
        if (! Schema::hasColumn('configuracoes', 'produtoFormImagePreviewSize')) {
            return 48;
        }

        $config = Configuracao::query()
            ->when($empresaId !== null, fn ($query) => $query->where('empresa_id', $empresaId))
            ->when($empresaId === null, fn ($query) => $query->whereNull('empresa_id'))
            ->first();

        return (int) ($config?->produtoFormImagePreviewSize ?? 48);
    }

    public function defaultCompanyDocument(User $user): string
    {
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);
        $empresaAtiva = EmpresaContext::activeEmpresa($user);
        $cnpjCpfEmpresa = (string) ($empresaAtiva?->cnpj_cpf ?? $user->documento());

        if ($empresaId) {
            $cnpjCpfEmpresa = (string) (Empresa::query()->find($empresaId)?->cnpj_cpf ?? $cnpjCpfEmpresa);
        }

        return $cnpjCpfEmpresa;
    }

    private function validateAndNormalize(int $empresaId, array $data, ?Produto $produto = null): array
    {
        $validated = validator($data, [
            'CODIGO' => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('produto', 'CODIGO')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($produto?->id),
            ],
            'NOME' => ['required', 'string', 'max:255'],
            'cnpj_cpf' => ['nullable', 'string', 'max:18'],
            'PRECO' => ['required', 'numeric', 'min:0'],
            'OFERTA' => ['nullable', 'numeric', 'min:0'],
            'IMG' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    if (! $this->isValidProdutoImagePathOrUrl($value)) {
                        $fail('Informe uma URL valida ou um caminho interno iniciando com /storage/ ou /storage-images/.');
                    }
                },
            ],
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'grupo_id' => ['required', 'exists:grupos,id'],
        ], [
            'CODIGO.unique' => 'Este código já existe para esta empresa.',
        ])->validate();

        $departamento = Departamento::query()->find($validated['departamento_id']);
        $grupo = Grupo::query()->find($validated['grupo_id']);

        if (! $departamento || ! $grupo) {
            abort(403);
        }

        if ((int) $grupo->departamento_id !== (int) $validated['departamento_id']) {
            throw ValidationException::withMessages([
                'grupo_id' => 'Grupo deve pertencer ao departamento selecionado',
            ]);
        }

        if ((int) $departamento->empresa_id !== $empresaId || (int) $grupo->empresa_id !== $empresaId) {
            abort(403, 'Departamento/Grupo fora da empresa ativa selecionada.');
        }

        $empresaDocumento = (string) ($validated['cnpj_cpf'] ?? '');
        $empresaDocumento = $empresaDocumento !== ''
            ? $empresaDocumento
            : (string) (Empresa::query()->find($empresaId)?->cnpj_cpf ?? '');

        $validated['cnpj_cpf'] = preg_replace('/\D/', '', $empresaDocumento);
        $validated['OFERTA'] = isset($validated['OFERTA']) && $validated['OFERTA'] !== ''
            ? (float) $validated['OFERTA']
            : 0;
        $validated['IMG'] = ImageStorage::normalizePublicUrl((string) ($validated['IMG'] ?? ''));
        $validated['empresa_id'] = $empresaId;

        return $validated;
    }

    private function usesEmpresaSegregation(): bool
    {
        return Schema::hasColumn('departamentos', 'empresa_id')
            && Schema::hasColumn('grupos', 'empresa_id')
            && Schema::hasColumn('produto', 'empresa_id');
    }

    private function isValidProdutoImagePathOrUrl(mixed $value): bool
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return true;
        }

        return ImageStorage::isValidImagePathOrUrl($normalized);
    }
}