<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SubscriptionPlansManagementPanel;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroConfig;
use App\Models\EmpresaSubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_open_subscription_plan_management_page(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.financeiro.plans.index'))
            ->assertOk()
            ->assertSeeLivewire(SubscriptionPlansManagementPanel::class);
    }

    public function test_default_admin_can_create_and_update_subscription_plan_via_livewire(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);

        Livewire::test(SubscriptionPlansManagementPanel::class)
            ->call('startCreate')
            ->set('code', 'teste-premium')
            ->set('name', 'Plano Teste Premium')
            ->set('description', 'Cobrança premium de teste.')
            ->set('intervaloCobrancaDias', (string) EmpresaFinanceiroConfig::INTERVALO_90_DIAS)
            ->set('valorUnitario', '89.90')
            ->set('trialDays', '7')
            ->set('sortOrder', '50')
            ->call('save')
            ->assertSee('Plano Plano Teste Premium criado com sucesso.');

        $plan = EmpresaSubscriptionPlan::query()->where('code', 'teste-premium')->firstOrFail();

        $this->assertDatabaseHas('empresa_subscription_plans', [
            'id' => $plan->id,
            'name' => 'Plano Teste Premium',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 89.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
        ]);

        Livewire::test(SubscriptionPlansManagementPanel::class)
            ->call('editPlan', $plan->id)
            ->set('name', 'Plano Teste Premium Atualizado')
            ->set('trialDays', '14')
            ->call('save')
            ->assertSee('Plano Plano Teste Premium Atualizado atualizado com sucesso.');

        $this->assertDatabaseHas('empresa_subscription_plans', [
            'id' => $plan->id,
            'name' => 'Plano Teste Premium Atualizado',
            'trial_days' => 14,
        ]);

        Livewire::test(SubscriptionPlansManagementPanel::class)
            ->call('toggleActive', $plan->id)
            ->assertSee('Plano Plano Teste Premium Atualizado desativado com sucesso.');

        $this->assertDatabaseHas('empresa_subscription_plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_revenda_cannot_access_subscription_plan_management_page(): void
    {
        $revenda = Empresa::query()->create([
            'codigo' => '920',
            'nome' => 'Revenda Sem Acesso',
            'fantasia' => 'Revenda Sem Acesso',
            'razaosocial' => 'Revenda Sem Acesso LTDA',
            'cnpj_cpf' => '08707221000106',
            'email' => 'revenda-sem-acesso@example.com',
            'fone' => '11999990000',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'api_token' => str_repeat('r', 60),
            'urlimagem' => '',
        ]);

        $user = User::factory()->create([
            'empresa_id' => $revenda->id,
            'menu_permissions' => [User::MENU_FINANCEIRO],
        ]);

        $this->actingAs($user)
            ->get(route('admin.financeiro.plans.index'))
            ->assertForbidden();
    }
}