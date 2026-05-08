<?php

namespace App\Livewire\Admin;

use App\Models\Departamento;
use App\Models\Grupo;
use App\Services\GrupoService;
use App\Support\EmpresaContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class GroupsManagementPanel extends Component
{
    use WithPagination;

    public string $nome = '';

    public string $departamentoId = '';

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

    public function save(GrupoService $grupoService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para cadastrar grupo.');
        }

        $grupoService->createForEmpresa($empresaId, [
            'nome' => $this->nome,
            'departamento_id' => $this->departamentoId,
        ]);

        $this->reset('nome', 'departamentoId');
        $this->resetPage();
        $this->statusMessage = 'Grupo criado com sucesso.';
        $this->resetErrorBag('delete');
    }

    public function deleteGroup(int $groupId): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        $group = Grupo::query()
            ->withCount('produtos')
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->findOrFail($groupId);

        if (($group->produtos_count ?? 0) > 0) {
            $this->addError('delete', 'Nao e possivel deletar grupo com produtos associados');
            return;
        }

        $name = $group->nome;
        $group->delete();

        $this->resetErrorBag('delete');
        $this->statusMessage = "Grupo {$name} deletado com sucesso.";
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $grupos = Grupo::query()
            ->with(['departamento', 'empresa:id,cnpj_cpf'])
            ->withCount('produtos')
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('nome')
            ->paginate(15);

        $departamentos = $empresaId
            ? Departamento::query()
                ->where('empresa_id', $empresaId)
                ->with('empresa:id,cnpj_cpf')
                ->orderBy('nome')
                ->get()
            : new Collection();

        return view('livewire.admin.groups-management-panel', [
            'grupos' => $grupos,
            'departamentos' => $departamentos,
            'canCreate' => $this->canCreate(),
            'indexUrl' => route('admin.grupos.index'),
        ]);
    }
}