<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DepartmentsManagementPanel;
use App\Livewire\Admin\DepartmentEditForm;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_create_department_via_livewire_component(): void
    {
        $empresa = Empresa::query()->create([
            'cnpj_cpf' => '12.345.678/0001-99',
            'nome' => 'Empresa Teste',
            'fantasia' => 'Empresa Teste',
            'razaosocial' => 'Empresa Teste LTDA',
            'urlimagem' => 'logo.png',
            'codigo' => '1001',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('a', 60),
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(DepartmentsManagementPanel::class)
            ->set('nome', 'Bebidas')
            ->call('save')
            ->assertSet('nome', '')
            ->assertSee('Departamento criado com sucesso.')
            ->assertSee('Bebidas');

        $this->assertDatabaseHas('departamentos', [
            'empresa_id' => $empresa->id,
            'nome' => 'Bebidas',
        ]);
    }

    public function test_default_admin_can_create_legacy_department_without_active_company(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);

        Livewire::test(DepartmentsManagementPanel::class)
            ->set('nome', 'Legado Sem Empresa')
            ->call('save')
            ->assertSet('nome', '')
            ->assertSee('Departamento criado com sucesso.')
            ->assertSee('Legado Sem Empresa');

        $this->assertDatabaseHas('departamentos', [
            'empresa_id' => null,
            'nome' => 'Legado Sem Empresa',
        ]);
    }

    public function test_department_livewire_component_validates_unique_name_per_company(): void
    {
        $empresa = Empresa::query()->create([
            'cnpj_cpf' => '98.765.432/0001-11',
            'nome' => 'Empresa Duplicada',
            'fantasia' => 'Empresa Duplicada',
            'razaosocial' => 'Empresa Duplicada LTDA',
            'urlimagem' => 'logo-duplicada.png',
            'codigo' => '1002',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('b', 60),
        ]);

        Departamento::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Higiene',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(DepartmentsManagementPanel::class)
            ->set('nome', 'Higiene')
            ->call('save')
            ->assertHasErrors(['nome' => 'unique']);
    }

    public function test_default_admin_can_update_department_via_livewire_edit_component(): void
    {
        $empresa = Empresa::query()->create([
            'cnpj_cpf' => '55.555.555/0001-55',
            'nome' => 'Empresa Edicao',
            'fantasia' => 'Empresa Edicao',
            'razaosocial' => 'Empresa Edicao LTDA',
            'urlimagem' => 'edicao.png',
            'codigo' => '1007',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('g', 60),
        ]);

        $departamento = Departamento::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Padaria',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(DepartmentEditForm::class, [
            'departamento' => $departamento,
            'returnUrl' => route('admin.departamentos.index'),
        ])
            ->set('nome', 'Padaria Atualizada')
            ->call('save')
            ->assertSee('Departamento atualizado com sucesso.');

        $this->assertDatabaseHas('departamentos', [
            'id' => $departamento->id,
            'nome' => 'Padaria Atualizada',
        ]);
    }

    public function test_default_admin_can_delete_department_via_livewire_component(): void
    {
        $empresa = Empresa::query()->create([
            'cnpj_cpf' => '77.777.777/0001-77',
            'nome' => 'Empresa Delete',
            'fantasia' => 'Empresa Delete',
            'razaosocial' => 'Empresa Delete LTDA',
            'urlimagem' => 'delete.png',
            'codigo' => '1009',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('i', 60),
        ]);

        $departamento = Departamento::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Excluir Departamento',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(DepartmentsManagementPanel::class)
            ->call('deleteDepartment', $departamento->id)
            ->assertSee('Departamento Excluir Departamento deletado com sucesso.');

        $this->assertDatabaseMissing('departamentos', [
            'id' => $departamento->id,
        ]);
    }
}