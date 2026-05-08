<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CompanyEditForm;
use App\Livewire\Admin\CompaniesManagementPanel;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_create_company_via_livewire_component(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $revenda = Empresa::query()->create([
            'codigo' => '900',
            'nome' => 'Revenda Base',
            'fantasia' => 'Revenda Base',
            'razaosocial' => 'Revenda Base LTDA',
            'cnpj_cpf' => '11222333000181',
            'email' => 'revenda@example.com',
            'fone' => '11999999999',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'api_token' => str_repeat('r', 60),
            'urlimagem' => '',
        ]);

        $this->actingAs($admin);

        Livewire::test(CompaniesManagementPanel::class)
            ->call('startCreate')
            ->set('nome', 'Empresa Livewire')
            ->set('razaosocial', 'Empresa Livewire LTDA')
            ->set('cnpjCpf', '12.345.678/0001-95')
            ->set('email', 'empresa-livewire@example.com')
            ->set('fone', '(11) 99999-8888')
            ->set('nivelAcesso', (string) Empresa::NIVEL_CLIENTE_FINAL)
            ->set('revendaId', (string) $revenda->id)
            ->set('endereco', 'Rua Um')
            ->set('bairro', 'Centro')
            ->set('numero', '10')
            ->set('cep', '01001-000')
            ->call('save')
            ->assertSee('Empresa Empresa Livewire criada com sucesso.');

        $this->assertDatabaseHas('empresa', [
            'nome' => 'Empresa Livewire',
            'fantasia' => 'Empresa Livewire',
            'razaosocial' => 'Empresa Livewire LTDA',
            'cnpj_cpf' => '12345678000195',
            'email' => 'empresa-livewire@example.com',
            'revenda_id' => $revenda->id,
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ]);
    }

    public function test_admin_companies_page_renders_livewire_panel_after_migration(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.empresas.index'))
            ->assertOk()
            ->assertSeeLivewire(CompaniesManagementPanel::class);
    }

    public function test_default_admin_can_update_company_via_livewire_edit_component(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $revenda = Empresa::query()->create([
            'codigo' => '901',
            'nome' => 'Revenda Edit',
            'fantasia' => 'Revenda Edit',
            'razaosocial' => 'Revenda Edit LTDA',
            'cnpj_cpf' => '11222333000182',
            'email' => 'revenda-edit@example.com',
            'fone' => '11999999998',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'api_token' => str_repeat('e', 60),
            'urlimagem' => '',
        ]);

        $empresa = Empresa::query()->create([
            'codigo' => '902',
            'nome' => 'Empresa Antiga',
            'fantasia' => 'Empresa Antiga',
            'razaosocial' => 'Empresa Antiga LTDA',
            'cnpj_cpf' => '12345678000195',
            'email' => 'empresa-antiga@example.com',
            'fone' => '11911112222',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => $revenda->id,
            'api_token' => str_repeat('a', 60),
            'urlimagem' => '',
        ]);

        $this->actingAs($admin);

        Livewire::test(CompanyEditForm::class, [
            'empresa' => $empresa,
            'returnUrl' => route('admin.empresas.index'),
        ])
            ->set('nome', 'Empresa Atualizada')
            ->set('razaosocial', 'Empresa Atualizada LTDA')
            ->set('cnpjCpf', '12.345.678/0001-95')
            ->set('email', 'empresa-atualizada@example.com')
            ->set('fone', '(11) 98888-7777')
            ->set('revendaId', (string) $revenda->id)
            ->set('endereco', 'Rua Dois')
            ->set('bairro', 'Bairro Novo')
            ->set('numero', '200')
            ->set('cep', '02002-000')
            ->call('save')
            ->assertSee('Empresa atualizada com sucesso.');

        $this->assertDatabaseHas('empresa', [
            'id' => $empresa->id,
            'nome' => 'Empresa Atualizada',
            'fantasia' => 'Empresa Atualizada',
            'razaosocial' => 'Empresa Atualizada LTDA',
            'cnpj_cpf' => '12345678000195',
            'email' => 'empresa-atualizada@example.com',
            'fone' => '(11) 98888-7777',
            'revenda_id' => $revenda->id,
        ]);
    }
}