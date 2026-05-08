<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ProductEditForm;
use App\Livewire\Admin\ProductsManagementPanel;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\Produto;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_create_product_via_livewire_component(): void
    {
        $empresa = $this->createEmpresa('55.111.111/0001-55', 'Empresa Produto Create', 'tok-create');
        [$departamento, $grupo] = $this->createDepartmentAndGroup($empresa, 'Bebidas', 'Refrigerantes');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(ProductsManagementPanel::class)
            ->set('codigo', '12345')
            ->set('nome', 'Produto Livewire')
            ->set('preco', '10.50')
            ->set('oferta', '8.90')
            ->set('img', '/storage-images/produto-livewire.png')
            ->set('departamentoId', (string) $departamento->id)
            ->set('grupoId', (string) $grupo->id)
            ->call('save')
            ->assertSee('Produto criado com sucesso.');

        $this->assertDatabaseHas('produto', [
            'CODIGO' => '12345',
            'NOME' => 'Produto Livewire',
            'empresa_id' => $empresa->id,
            'departamento_id' => $departamento->id,
            'grupo_id' => $grupo->id,
            'IMG' => '/storage-images/produto-livewire.png',
        ]);
    }

    public function test_default_admin_can_update_product_via_livewire_edit_component(): void
    {
        $empresa = $this->createEmpresa('66.222.222/0001-66', 'Empresa Produto Edit', 'tok-edit');
        [$departamentoA, $grupoA] = $this->createDepartmentAndGroup($empresa, 'Mercearia', 'Massas');
        [$departamentoB, $grupoB] = $this->createDepartmentAndGroup($empresa, 'Padaria', 'Bolos');

        $produto = Produto::query()->create([
            'CODIGO' => '777',
            'NOME' => 'Produto Antigo',
            'cnpj_cpf' => '66222222000166',
            'empresa_id' => $empresa->id,
            'PRECO' => 20.00,
            'OFERTA' => 0,
            'IMG' => '/storage-images/old.png',
            'departamento_id' => $departamentoA->id,
            'grupo_id' => $grupoA->id,
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(ProductEditForm::class, [
            'produto' => $produto,
            'returnUrl' => route('admin.produtos.index'),
        ])
            ->set('codigo', '888')
            ->set('nome', 'Produto Atualizado')
            ->set('cnpjCpf', '66.222.222/0001-66')
            ->set('preco', '30.40')
            ->set('oferta', '25.10')
            ->set('img', '/storage-images/new.png')
            ->set('departamentoId', (string) $departamentoB->id)
            ->set('grupoId', (string) $grupoB->id)
            ->call('save')
            ->assertSee('Produto atualizado com sucesso.');

        $this->assertDatabaseHas('produto', [
            'id' => $produto->id,
            'CODIGO' => '888',
            'NOME' => 'Produto Atualizado',
            'departamento_id' => $departamentoB->id,
            'grupo_id' => $grupoB->id,
            'IMG' => '/storage-images/new.png',
        ]);
    }

    public function test_product_update_normalizes_legacy_gallery_storage_path(): void
    {
        $empresa = $this->createEmpresa('69.222.222/0001-69', 'Empresa Produto Legacy', 'tok-legacy');
        [$departamento, $grupo] = $this->createDepartmentAndGroup($empresa, 'Limpeza', 'Desinfetantes');

        $produto = Produto::query()->create([
            'CODIGO' => '555',
            'NOME' => 'Produto Legacy',
            'cnpj_cpf' => '69222222000169',
            'empresa_id' => $empresa->id,
            'PRECO' => 11.00,
            'OFERTA' => 0,
            'IMG' => '/storage/galeria-nova/28850228088302.png',
            'departamento_id' => $departamento->id,
            'grupo_id' => $grupo->id,
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(ProductEditForm::class, [
            'produto' => $produto,
            'returnUrl' => route('admin.produtos.index'),
        ])
            ->set('img', '/storage/galeria-nova/28850228088302.png')
            ->call('save')
            ->assertSee('Produto atualizado com sucesso.');

        $this->assertDatabaseHas('produto', [
            'id' => $produto->id,
            'IMG' => '/storage-images/galeria-nova/28850228088302.png',
        ]);
    }

    public function test_default_admin_can_delete_product_via_livewire_component(): void
    {
        $empresa = $this->createEmpresa('77.333.333/0001-77', 'Empresa Produto Delete', 'tok-delete');
        [$departamento, $grupo] = $this->createDepartmentAndGroup($empresa, 'Congelados', 'Sorvetes');

        $produto = Produto::query()->create([
            'CODIGO' => '999',
            'NOME' => 'Produto Deletar',
            'cnpj_cpf' => '77333333000177',
            'empresa_id' => $empresa->id,
            'PRECO' => 15.00,
            'OFERTA' => 12.00,
            'IMG' => '/storage-images/delete.png',
            'departamento_id' => $departamento->id,
            'grupo_id' => $grupo->id,
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(ProductsManagementPanel::class)
            ->call('deleteProduct', $produto->id)
            ->assertSee('Produto 999 deletado com sucesso.');

        $this->assertDatabaseMissing('produto', [
            'id' => $produto->id,
        ]);
    }

    public function test_products_pages_render_gallery_picker_link_after_livewire_migration(): void
    {
        $empresa = $this->createEmpresa('88.444.444/0001-88', 'Empresa Produto Link', 'tok-link');
        [$departamento, $grupo] = $this->createDepartmentAndGroup($empresa, 'Higiene', 'Sabonetes');

        $produto = Produto::query()->create([
            'CODIGO' => '321',
            'NOME' => 'Produto Link',
            'cnpj_cpf' => '88444444000188',
            'empresa_id' => $empresa->id,
            'PRECO' => 9.90,
            'OFERTA' => 0,
            'IMG' => '',
            'departamento_id' => $departamento->id,
            'grupo_id' => $grupo->id,
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        $galleryUrl = route('admin.galeria-imagem.index', ['abrir_form' => 1, 'selecionar_produto' => 1]);

        $this->get(route('admin.produtos.index'))
            ->assertOk()
            ->assertSee($galleryUrl);

        $this->get(route('admin.produtos.edit', ['produto' => $produto->id, 'return' => route('admin.produtos.index')]))
            ->assertOk()
            ->assertSee($galleryUrl);
    }

    private function createEmpresa(string $cnpjCpf, string $nome, string $token): Empresa
    {
        return Empresa::query()->create([
            'cnpj_cpf' => $cnpjCpf,
            'nome' => $nome,
            'fantasia' => $nome,
            'razaosocial' => $nome.' LTDA',
            'urlimagem' => 'empresa.png',
            'codigo' => substr(preg_replace('/\D/', '', $cnpjCpf), 0, 4),
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_pad($token, 60, 'x'),
        ]);
    }

    private function createDepartmentAndGroup(Empresa $empresa, string $departmentName, string $groupName): array
    {
        $department = Departamento::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => $departmentName,
        ]);

        $group = Grupo::query()->create([
            'empresa_id' => $empresa->id,
            'departamento_id' => $department->id,
            'nome' => $groupName,
        ]);

        return [$department, $group];
    }
}