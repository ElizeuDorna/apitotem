<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use App\Services\EmpresaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CompaniesManagementPanel extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $formEnabled = false;

    public string $nome = '';

    public string $razaosocial = '';

    public string $cnpjCpf = '';

    public string $email = '';

    public string $fone = '';

    public string $senhaIntegracaoApi = '';

    public string $nivelAcesso = '1';

    public string $revendaId = '';

    public string $endereco = '';

    public string $bairro = '';

    public string $numero = '';

    public string $cep = '';

    public bool $publicPageEnabled = false;

    public string $publicPageSlug = '';

    public ?string $statusMessage = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->formEnabled = true;
        $this->statusMessage = null;
        $this->resetValidation();
    }

    public function cancelCreate(): void
    {
        $this->resetForm();
        $this->statusMessage = null;
    }

    public function save(EmpresaService $empresaService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresa = $empresaService->createForUser($user, $this->payload());

        $this->resetForm();
        $this->resetPage();
        $this->statusMessage = "Empresa {$empresa->nome} criada com sucesso.";
    }

    public function deleteCompany(int $companyId, EmpresaService $empresaService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresa = Empresa::query()->findOrFail($companyId);
        $nome = $empresa->nome;

        $empresaService->deleteForUser($user, $empresa);

        $this->resetPage();
        $this->statusMessage = "Empresa {$nome} deletada com sucesso.";
    }

    public function render(EmpresaService $empresaService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        [$empresaAtivaId, $empresaAtivaNome] = $empresaService->activeEmpresaSummary($user);

        return view('livewire.admin.companies-management-panel', [
            'empresas' => $empresaService->queryForUser($user, $this->search)->paginate(15),
            'empresaAtivaId' => $empresaAtivaId,
            'empresaAtivaNome' => $empresaAtivaNome,
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'empresaVinculada' => $user->empresa,
            'podePesquisar' => $empresaService->canSearch($user),
            'revendas' => $empresaService->availableRevendas($user),
        ]);
    }

    private function payload(): array
    {
        return [
            'nome' => $this->nome,
            'razaosocial' => $this->razaosocial,
            'cnpj_cpf' => $this->cnpjCpf,
            'email' => $this->email,
            'fone' => $this->fone,
            'senha_integracao_api' => $this->senhaIntegracaoApi,
            'nivel_acesso' => $this->nivelAcesso,
            'revenda_id' => $this->revendaId !== '' ? $this->revendaId : null,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'numero' => $this->numero,
            'cep' => $this->cep,
            'public_page_enabled' => $this->publicPageEnabled,
            'public_page_slug' => $this->publicPageSlug,
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'formEnabled',
            'nome',
            'razaosocial',
            'cnpjCpf',
            'email',
            'fone',
            'senhaIntegracaoApi',
            'revendaId',
            'endereco',
            'bairro',
            'numero',
            'cep',
            'publicPageEnabled',
            'publicPageSlug',
        ]);

        $this->nivelAcesso = '1';
        $this->resetValidation();
    }
}