<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CompanyEditForm;
use App\Livewire\Admin\CompaniesManagementPanel;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroConfig;
use App\Models\EmpresaSubscription;
use App\Models\User;
use App\Services\EmpresaOnboardingService;
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
            'cadastro_origem' => Empresa::CADASTRO_ORIGEM_ADMIN,
        ]);

        $empresa = Empresa::query()->where('email', 'empresa-livewire@example.com')->firstOrFail();

        $this->assertDatabaseHas('empresa_financeiro_configs', [
            'empresa_id' => $empresa->id,
            'asaas_integration_ativa' => false,
            'cobranca_automatica_ativa' => false,
        ]);

        $this->assertDatabaseHas('empresa_subscriptions', [
            'empresa_id' => $empresa->id,
            'status' => EmpresaSubscription::STATUS_ACTIVE,
            'plan_name' => 'Cadastro gerenciado',
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

    public function test_revenda_can_open_company_index_and_only_see_linked_companies(): void
    {
        $revenda = Empresa::query()->create([
            'codigo' => '910',
            'nome' => 'Revenda Fluxo',
            'fantasia' => 'Revenda Fluxo',
            'razaosocial' => 'Revenda Fluxo LTDA',
            'cnpj_cpf' => '08707221000106',
            'email' => 'revenda-fluxo@example.com',
            'fone' => '11999990000',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'api_token' => str_repeat('f', 60),
            'urlimagem' => '',
        ]);

        $empresaVinculada = Empresa::query()->create([
            'codigo' => '911',
            'nome' => 'Cliente Vinculado',
            'fantasia' => 'Cliente Vinculado',
            'razaosocial' => 'Cliente Vinculado LTDA',
            'cnpj_cpf' => '83198688000193',
            'email' => 'cliente-vinculado@example.com',
            'fone' => '11999991111',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => $revenda->id,
            'api_token' => str_repeat('v', 60),
            'urlimagem' => '',
        ]);

        Empresa::query()->create([
            'codigo' => '912',
            'nome' => 'Cliente Externo',
            'fantasia' => 'Cliente Externo',
            'razaosocial' => 'Cliente Externo LTDA',
            'cnpj_cpf' => '11222333000181',
            'email' => 'cliente-externo@example.com',
            'fone' => '11999992222',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('x', 60),
            'urlimagem' => '',
        ]);

        $revendaUser = User::factory()->create([
            'empresa_id' => $revenda->id,
            'menu_permissions' => [],
        ]);

        $this->actingAs($revendaUser)
            ->get(route('admin.empresas.index'))
            ->assertOk()
            ->assertSeeLivewire(CompaniesManagementPanel::class)
            ->assertSee($empresaVinculada->nome)
            ->assertDontSee('Cliente Externo');
    }

    public function test_revenda_user_resolved_by_document_can_open_company_index_and_only_see_linked_companies(): void
    {
        $revenda = Empresa::query()->create([
            'codigo' => '913',
            'nome' => 'Revenda Documento',
            'fantasia' => 'Revenda Documento',
            'razaosocial' => 'Revenda Documento LTDA',
            'cnpj_cpf' => '08707221000106',
            'email' => 'revenda-documento@example.com',
            'fone' => '11999993333',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'api_token' => str_repeat('d', 60),
            'urlimagem' => '',
        ]);

        $empresaVinculada = Empresa::query()->create([
            'codigo' => '914',
            'nome' => 'Cliente Documento',
            'fantasia' => 'Cliente Documento',
            'razaosocial' => 'Cliente Documento LTDA',
            'cnpj_cpf' => '83198688000193',
            'email' => 'cliente-documento@example.com',
            'fone' => '11999994444',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => $revenda->id,
            'api_token' => str_repeat('c', 60),
            'urlimagem' => '',
        ]);

        Empresa::query()->create([
            'codigo' => '915',
            'nome' => 'Cliente Fora Documento',
            'fantasia' => 'Cliente Fora Documento',
            'razaosocial' => 'Cliente Fora Documento LTDA',
            'cnpj_cpf' => '11222333000181',
            'email' => 'cliente-fora-documento@example.com',
            'fone' => '11999995555',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('o', 60),
            'urlimagem' => '',
        ]);

        $revendaUser = User::factory()->create([
            'cpf' => '08707221000106',
            'empresa_id' => null,
            'menu_permissions' => ['produtos'],
        ]);

        $this->actingAs($revendaUser)
            ->get(route('admin.empresas.index'))
            ->assertOk()
            ->assertSeeLivewire(CompaniesManagementPanel::class)
            ->assertSee($empresaVinculada->nome)
            ->assertDontSee('Cliente Fora Documento');
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

    public function test_revenda_created_company_is_provisioned_with_managed_defaults(): void
    {
        $revenda = Empresa::query()->create([
            'codigo' => '920',
            'nome' => 'Revenda Provisionamento',
            'fantasia' => 'Revenda Provisionamento',
            'razaosocial' => 'Revenda Provisionamento LTDA',
            'cnpj_cpf' => '08707221000107',
            'email' => 'revenda-provisionamento@example.com',
            'fone' => '11999996666',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'api_token' => str_repeat('p', 60),
            'urlimagem' => '',
        ]);

        $revendaUser = User::factory()->create([
            'empresa_id' => $revenda->id,
            'menu_permissions' => [],
        ]);

        $this->actingAs($revendaUser);

        Livewire::test(CompaniesManagementPanel::class)
            ->call('startCreate')
            ->set('nome', 'Cliente Revenda Provisionado')
            ->set('razaosocial', 'Cliente Revenda Provisionado LTDA')
            ->set('cnpjCpf', '83.198.688/0001-93')
            ->set('email', 'cliente-revenda@example.com')
            ->set('fone', '(11) 97777-0000')
            ->call('save');

        $empresa = Empresa::query()->where('email', 'cliente-revenda@example.com')->firstOrFail();

        $this->assertSame(Empresa::CADASTRO_ORIGEM_REVENDA, $empresa->cadastro_origem);
        $this->assertSame((int) $revenda->id, (int) $empresa->revenda_id);

        $config = EmpresaFinanceiroConfig::query()->where('empresa_id', $empresa->id)->firstOrFail();

        $this->assertFalse((bool) $config->asaas_integration_ativa);
        $this->assertFalse((bool) $config->cobranca_automatica_ativa);
    }

    public function test_self_service_provisioning_enables_asaas_and_automatic_billing_by_default(): void
    {
        $empresa = Empresa::query()->create([
            'codigo' => '930',
            'nome' => 'Cliente Self Service',
            'fantasia' => 'Cliente Self Service',
            'razaosocial' => 'Cliente Self Service LTDA',
            'cnpj_cpf' => '54132168000190',
            'email' => 'cliente-self@example.com',
            'fone' => '11999997777',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'cadastro_origem' => Empresa::CADASTRO_ORIGEM_SELF_SERVICE,
            'api_token' => str_repeat('s', 60),
            'urlimagem' => '',
        ]);

        app(EmpresaOnboardingService::class)->provisionSelfServiceClient($empresa, [
            'plan_name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 49.90,
        ], [
            'starts_at' => now()->startOfDay(),
            'access_expires_at' => now()->addDays(90)->startOfDay(),
        ]);

        $this->assertDatabaseHas('empresa_financeiro_configs', [
            'empresa_id' => $empresa->id,
            'asaas_integration_ativa' => true,
            'cobranca_automatica_ativa' => true,
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_receber_unitario' => 49.90,
        ]);

        $this->assertDatabaseHas('empresa_subscriptions', [
            'empresa_id' => $empresa->id,
            'status' => EmpresaSubscription::STATUS_ACTIVE,
            'plan_name' => 'Plano Trimestral',
            'access_expires_at' => now()->addDays(90)->startOfDay()->toDateString(),
        ]);
    }
}