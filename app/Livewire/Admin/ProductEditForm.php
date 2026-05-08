<?php

namespace App\Livewire\Admin;

use App\Models\Produto;
use App\Services\ProdutoService;
use App\Support\EmpresaContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProductEditForm extends Component
{
    public Produto $produto;

    public string $codigo = '';

    public string $nome = '';

    public string $cnpjCpf = '';

    public string $preco = '';

    public string $oferta = '0';

    public string $img = '';

    public string $departamentoId = '';

    public string $grupoId = '';

    public string $returnUrl = '';

    public ?string $statusMessage = null;

    public function mount(Produto $produto, string $returnUrl): void
    {
        $this->produto = $produto;
        $this->codigo = (string) ($produto->CODIGO ?? '');
        $this->nome = (string) ($produto->NOME ?? '');
        $this->cnpjCpf = (string) ($produto->cnpj_cpf ?? '');
        $this->preco = (string) ($produto->PRECO ?? '');
        $this->oferta = (string) ($produto->OFERTA ?? '0');
        $this->img = (string) ($produto->IMG ?? '');
        $this->departamentoId = (string) ($produto->departamento_id ?? '');
        $this->grupoId = (string) ($produto->grupo_id ?? '');
        $this->returnUrl = $returnUrl;
    }

    public function updatedDepartamentoId(): void
    {
        $this->grupoId = '';
    }

    public function save(ProdutoService $produtoService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $this->produto = $produtoService->updateForUser($user, $this->produto, [
            'CODIGO' => $this->codigo,
            'NOME' => $this->nome,
            'cnpj_cpf' => $this->cnpjCpf,
            'PRECO' => $this->preco,
            'OFERTA' => $this->oferta,
            'IMG' => $this->img,
            'departamento_id' => $this->departamentoId,
            'grupo_id' => $this->grupoId,
        ]);

        $this->fillFromProduct($this->produto);
        $this->statusMessage = 'Produto atualizado com sucesso.';
    }

    public function render(ProdutoService $produtoService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $produtoService->authorizeProdutoAccess($user, $this->produto);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        $departments = $produtoService->departmentsQueryForUser($user)
            ->when($empresaId !== null, fn ($query) => $query->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get();

        $groupsBase = $produtoService->groupsQueryForUser($user)
            ->when($empresaId !== null, fn ($query) => $query->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get();

        $availableGroups = $this->departamentoId !== ''
            ? $groupsBase->where('departamento_id', (int) $this->departamentoId)->values()
            : new Collection();

        return view('livewire.admin.product-edit-form', [
            'departments' => $departments,
            'availableGroups' => $availableGroups,
            'galleryPickerUrl' => route('admin.galeria-imagem.index', ['abrir_form' => 1, 'selecionar_produto' => 1]),
            'previewSize' => $produtoService->resolvePreviewSize($empresaId),
        ]);
    }

    private function fillFromProduct(Produto $produto): void
    {
        $this->codigo = (string) ($produto->CODIGO ?? '');
        $this->nome = (string) ($produto->NOME ?? '');
        $this->cnpjCpf = (string) ($produto->cnpj_cpf ?? '');
        $this->preco = (string) ($produto->PRECO ?? '');
        $this->oferta = (string) ($produto->OFERTA ?? '0');
        $this->img = (string) ($produto->IMG ?? '');
        $this->departamentoId = (string) ($produto->departamento_id ?? '');
        $this->grupoId = (string) ($produto->grupo_id ?? '');
    }
}