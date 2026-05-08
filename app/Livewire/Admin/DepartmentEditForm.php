<?php

namespace App\Livewire\Admin;

use App\Models\Departamento;
use App\Services\DepartamentoService;
use App\Support\EmpresaContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DepartmentEditForm extends Component
{
    public Departamento $departamento;

    public string $nome = '';

    public string $returnUrl = '';

    public ?string $statusMessage = null;

    public function mount(Departamento $departamento, string $returnUrl): void
    {
        $this->departamento = $departamento;
        $this->nome = (string) $departamento->nome;
        $this->returnUrl = $returnUrl;
    }

    public function save(DepartamentoService $departamentoService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if ($user->isDefaultAdmin() && ! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para atualizar departamento.');
        }

        $departamentoService->updateForEmpresa($this->departamento, (int) $empresaId, [
            'nome' => $this->nome,
        ]);

        $this->departamento->refresh();
        $this->nome = (string) $this->departamento->nome;
        $this->statusMessage = 'Departamento atualizado com sucesso.';
    }

    public function render()
    {
        return view('livewire.admin.department-edit-form');
    }
}