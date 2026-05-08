<?php

namespace App\Livewire\Admin;

use App\Models\Produto;
use App\Services\ProdutoService;
use App\Support\EmpresaContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsManagementPanel extends Component
{
    use WithPagination;

    public string $codigo = '';

    public string $nome = '';

    public string $cnpjCpf = '';

    public string $preco = '';

    public string $oferta = '0';

    public string $img = '';

    public string $departamentoId = '';

    public string $grupoId = '';

    public ?string $statusMessage = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(ProdutoService $produtoService): void
    {
        $user = Auth::user();

        if ($user) {
            $this->cnpjCpf = $produtoService->defaultCompanyDocument($user);
        }
    }

    public function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return EmpresaContext::resolveEmpresaIdForUser($user) !== null;
    }

    public function updatedDepartamentoId(): void
    {
        $this->grupoId = '';
    }

    public function save(ProdutoService $produtoService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $produtoService->createForUser($user, $this->payload());

        $this->resetForm($produtoService, $user);
        $this->resetPage();
        $this->resetErrorBag('delete');
        $this->statusMessage = 'Produto criado com sucesso.';
    }

    public function deleteProduct(int $productId, ProdutoService $produtoService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $product = Produto::query()->findOrFail($productId);
        $codigo = (string) $product->CODIGO;

        $produtoService->deleteForUser($user, $product);

        $this->resetErrorBag('delete');
        $this->statusMessage = "Produto {$codigo} deletado com sucesso.";
    }

    public function render(ProdutoService $produtoService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $produtoService->ensureIndexAccess($user);

        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);
        $canCreate = $this->canCreate();

        $products = $produtoService->productsQueryForUser($user)
            ->orderBy('NOME')
            ->paginate(15);

        $departments = $canCreate
            ? $produtoService->departmentsQueryForUser($user)
                ->when($empresaId !== null, fn ($query) => $query->where('empresa_id', $empresaId))
                ->orderBy('nome')
                ->get()
            : new Collection();

        $groups = $canCreate
            ? $produtoService->groupsQueryForUser($user)
                ->when($empresaId !== null, fn ($query) => $query->where('empresa_id', $empresaId))
                ->orderBy('nome')
                ->get()
            : new Collection();

        $availableGroups = $this->departamentoId !== ''
            ? $groups->where('departamento_id', (int) $this->departamentoId)->values()
            : new Collection();

        return view('livewire.admin.products-management-panel', [
            'products' => $products,
            'departments' => $departments,
            'availableGroups' => $availableGroups,
            'canCreate' => $canCreate,
            'galleryPickerUrl' => route('admin.galeria-imagem.index', ['abrir_form' => 1, 'selecionar_produto' => 1]),
            'previewSize' => $produtoService->resolvePreviewSize($empresaId),
            'indexUrl' => route('admin.produtos.index'),
        ]);
    }

    private function payload(): array
    {
        return [
            'CODIGO' => $this->codigo,
            'NOME' => $this->nome,
            'cnpj_cpf' => $this->cnpjCpf,
            'PRECO' => $this->preco,
            'OFERTA' => $this->oferta,
            'IMG' => $this->img,
            'departamento_id' => $this->departamentoId,
            'grupo_id' => $this->grupoId,
        ];
    }

    private function resetForm(ProdutoService $produtoService, $user): void
    {
        $this->reset(['codigo', 'nome', 'preco', 'oferta', 'img', 'departamentoId', 'grupoId']);
        $this->oferta = '0';
        $this->cnpjCpf = $produtoService->defaultCompanyDocument($user);
    }
}