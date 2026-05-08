<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use App\Services\EmpresaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CompanyEditForm extends Component
{
    public Empresa $empresa;

    public string $returnUrl = '';

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

    public function mount(Empresa $empresa, string $returnUrl = ''): void
    {
        $this->empresa = $empresa;
        $this->returnUrl = $returnUrl !== '' ? $returnUrl : route('admin.empresas.index');
        $this->fillFromCompany($empresa);
    }

    public function save(EmpresaService $empresaService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $this->empresa = $empresaService->updateForUser($user, $this->empresa, $this->payload());
        $this->fillFromCompany($this->empresa);
        $this->statusMessage = 'Empresa atualizada com sucesso.';
    }

    public function render(EmpresaService $empresaService)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $empresaService->authorizeEmpresaAccess($user, $this->empresa);

        return view('livewire.admin.company-edit-form', [
            'isDefaultAdmin' => $user->isDefaultAdmin(),
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

    private function fillFromCompany(Empresa $empresa): void
    {
        $this->nome = (string) $empresa->nome;
        $this->razaosocial = (string) $empresa->razaosocial;
        $this->cnpjCpf = (string) $empresa->cnpj_cpf;
        $this->email = (string) $empresa->email;
        $this->fone = (string) $empresa->fone;
        $this->senhaIntegracaoApi = '';
        $this->nivelAcesso = (string) ((int) ($empresa->nivel_acesso ?? Empresa::NIVEL_CLIENTE_FINAL));
        $this->revendaId = $empresa->revenda_id !== null ? (string) $empresa->revenda_id : '';
        $this->endereco = (string) ($empresa->endereco ?? '');
        $this->bairro = (string) ($empresa->bairro ?? '');
        $this->numero = (string) ($empresa->numero ?? '');
        $this->cep = (string) ($empresa->cep ?? '');
        $this->publicPageEnabled = (bool) $empresa->public_page_enabled;
        $this->publicPageSlug = (string) ($empresa->public_page_slug ?? '');
        $this->resetValidation();
    }
}