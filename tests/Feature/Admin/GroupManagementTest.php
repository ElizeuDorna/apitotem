<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\GroupsManagementPanel;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_create_group_via_livewire_component(): void
    {
        $empresa = Empresa::query()->create([
            'cnpj_cpf' => '11.111.111/0001-11',
            'nome' => 'Empresa Grupo',
            'fantasia' => 'Empresa Grupo',
            'razaosocial' => 'Empresa Grupo LTDA',
            'urlimagem' => 'grupo.png',
            'codigo' => '1003',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('c', 60),
        ]);

        $departamento = Departamento::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Bebidas',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(GroupsManagementPanel::class)
            ->set('nome', 'Refrigerantes')
            ->set('departamentoId', (string) $departamento->id)
            ->call('save')
            ->assertSet('nome', '')
            ->assertSet('departamentoId', '')
            ->assertSee('Grupo criado com sucesso.')
            ->assertSee('Refrigerantes');

        $this->assertDatabaseHas('grupos', [
            'empresa_id' => $empresa->id,
            'departamento_id' => $departamento->id,
            'nome' => 'Refrigerantes',
        ]);
    }

    public function test_group_livewire_component_validates_unique_name_per_company(): void
    {
        $empresa = Empresa::query()->create([
            'cnpj_cpf' => '22.222.222/0001-22',
            'nome' => 'Empresa Unica',
            'fantasia' => 'Empresa Unica',
            'razaosocial' => 'Empresa Unica LTDA',
            'urlimagem' => 'grupo-unico.png',
            'codigo' => '1004',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('d', 60),
        ]);

        $departamento = Departamento::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'Higiene',
        ]);

        Grupo::query()->create([
            'empresa_id' => $empresa->id,
            'departamento_id' => $departamento->id,
            'nome' => 'Sabonetes',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresa->id]);

        Livewire::test(GroupsManagementPanel::class)
            ->set('nome', 'Sabonetes')
            ->set('departamentoId', (string) $departamento->id)
            ->call('save')
            ->assertHasErrors(['nome' => 'unique']);
    }

    public function test_group_livewire_component_rejects_department_from_another_company(): void
    {
        $empresaAtiva = Empresa::query()->create([
            'cnpj_cpf' => '33.333.333/0001-33',
            'nome' => 'Empresa Ativa',
            'fantasia' => 'Empresa Ativa',
            'razaosocial' => 'Empresa Ativa LTDA',
            'urlimagem' => 'empresa-ativa.png',
            'codigo' => '1005',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('e', 60),
        ]);

        $outraEmpresa = Empresa::query()->create([
            'cnpj_cpf' => '44.444.444/0001-44',
            'nome' => 'Outra Empresa',
            'fantasia' => 'Outra Empresa',
            'razaosocial' => 'Outra Empresa LTDA',
            'urlimagem' => 'outra-empresa.png',
            'codigo' => '1006',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('f', 60),
        ]);

        $departamentoEstrangeiro = Departamento::query()->create([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'Estrangeiro',
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);
        session([EmpresaContext::ADMIN_SESSION_KEY => $empresaAtiva->id]);

        Livewire::test(GroupsManagementPanel::class)
            ->set('nome', 'Tentativa Invalida')
            ->set('departamentoId', (string) $departamentoEstrangeiro->id)
            ->call('save')
            ->assertHasErrors(['departamento_id']);
    }
}