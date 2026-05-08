<?php

namespace App\Livewire\Admin;

use App\Models\Departamento;
use App\Models\Grupo;
use App\Services\GrupoService;
use App\Support\EmpresaContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GroupEditForm extends Component
{
    public Grupo $grupo;

    public string $nome = '';

    public string $departamentoId = '';

    public string $returnUrl = '';

    public ?string $statusMessage = null;

    public function mount(Grupo $grupo, string $returnUrl): void
    {
        $this->grupo = $grupo;
        $this->nome = (string) $grupo->nome;
        $this->departamentoId = (string) $grupo->departamento_id;
        $this->returnUrl = $returnUrl;
    }

    public function save(GrupoService $grupoService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para atualizar grupo.');
        }

        $grupoService->updateForEmpresa($this->grupo, (int) $empresaId, [
            'nome' => $this->nome,
            'departamento_id' => $this->departamentoId,
        ]);

        $this->grupo->refresh();
        $this->nome = (string) $this->grupo->nome;
        $this->departamentoId = (string) $this->grupo->departamento_id;
        $this->statusMessage = 'Grupo atualizado com sucesso.';
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        $departamentos = $empresaId
            ? Departamento::query()
                ->where('empresa_id', $empresaId)
                ->with('empresa:id,cnpj_cpf')
                ->orderBy('nome')
                ->get()
            : new Collection();

        return view('livewire.admin.group-edit-form', [
            'departamentos' => $departamentos,
        ]);
    }
}