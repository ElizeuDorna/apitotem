<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DepartmentsManagementPanel;
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
}