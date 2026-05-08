<?php

namespace App\Livewire\Admin;

use App\Models\Departamento;
use App\Services\DepartamentoService;
use App\Support\EmpresaContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentsManagementPanel extends Component
{
    use WithPagination;

    public string $nome = '';

    public ?string $statusMessage = null;

    protected string $paginationTheme = 'tailwind';

    public function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return (bool) EmpresaContext::resolveEmpresaIdForUser($user);
    }

    public function save(DepartamentoService $departamentoService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar departamento.');
        }

        $departamentoService->createForEmpresa($empresaId, [
            'nome' => $this->nome,
        ]);

        $this->reset('nome');
        $this->resetPage();
        $this->statusMessage = 'Departamento criado com sucesso.';
        $this->resetErrorBag('delete');
    }

    public function deleteDepartment(int $departmentId): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        $department = Departamento::query()
            ->withCount('grupos', 'produtos')
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->findOrFail($departmentId);

        if (($department->produtos_count ?? 0) > 0 || ($department->grupos_count ?? 0) > 0) {
            $this->addError('delete', 'Nao e possivel deletar departamento com produtos ou grupos associados');
            return;
        }

        $name = $department->nome;
        $department->delete();

        $this->resetErrorBag('delete');
        $this->statusMessage = "Departamento {$name} deletado com sucesso.";
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $departamentos = Departamento::query()
            ->with('empresa:id,cnpj_cpf')
            ->withCount('grupos', 'produtos')
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('nome')
            ->paginate(15);

        $empresaCnpjCpf = $empresaId ? optional(EmpresaContext::activeEmpresa($user))->cnpj_cpf : null;

        return view('livewire.admin.departments-management-panel', [
            'departamentos' => $departamentos,
            'empresaCnpjCpf' => $empresaCnpjCpf,
            'canCreate' => $this->canCreate(),
            'indexUrl' => route('admin.departamentos.index'),
        ]);
    }
}