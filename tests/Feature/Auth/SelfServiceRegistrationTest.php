<?php

namespace Tests\Feature\Auth;

use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroConfig;
use App\Models\EmpresaSubscription;
use App\Models\EmpresaSubscriptionPlan;
use App\Models\User;
use App\Models\WebScreenModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SelfServiceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_service_registration_screen_can_be_rendered(): void
    {
        EmpresaSubscriptionPlan::query()->updateOrCreate([
            'code' => 'trimestral',
        ], [
            'name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 59.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        $this->get(route('self-service.register'))
            ->assertOk()
            ->assertSee('Criar conta da empresa')
            ->assertSee('Plano Trimestral');
    }

    public function test_guest_can_register_company_via_self_service_flow(): void
    {
        $plan = EmpresaSubscriptionPlan::query()->updateOrCreate([
            'code' => 'trimestral',
        ], [
            'name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 59.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        $response = $this->post(route('self-service.register.store'), [
            'company_name' => 'Empresa Auto Cadastro',
            'company_social_name' => 'Empresa Auto Cadastro LTDA',
            'company_document' => '12.345.678/0001-95',
            'company_email' => 'empresa-autocadastro@example.com',
            'company_phone' => '(11) 98888-7777',
            'owner_name' => 'Responsavel Auto Cadastro',
            'owner_email' => 'responsavel@example.com',
            'owner_document' => '97779474100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_code' => 'trimestral',
        ]);

        $empresa = Empresa::query()->where('email', 'empresa-autocadastro@example.com')->firstOrFail();
        $user = User::query()->where('email', 'responsavel@example.com')->firstOrFail();

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->assertSame(Empresa::CADASTRO_ORIGEM_SELF_SERVICE, $empresa->cadastro_origem);
        $this->assertSame((int) $empresa->id, (int) $user->empresa_id);
        $this->assertSame(User::defaultSelfServiceMenuPermissions(), $user->menu_permissions);

        $this->assertDatabaseHas('empresa_financeiro_configs', [
            'empresa_id' => $empresa->id,
            'asaas_integration_ativa' => true,
            'cobranca_automatica_ativa' => true,
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_receber_unitario' => 59.90,
        ]);

        $this->assertDatabaseHas('empresa_subscriptions', [
            'empresa_id' => $empresa->id,
            'subscription_plan_id' => $plan->id,
            'status' => EmpresaSubscription::STATUS_TRIAL,
            'plan_name' => 'Plano Trimestral',
        ]);
    }

    public function test_guest_can_register_with_masked_documents_without_frontend_javascript(): void
    {
        EmpresaSubscriptionPlan::query()->updateOrCreate([
            'code' => 'trimestral',
        ], [
            'name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 59.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        $this->post(route('self-service.register.store'), [
            'company_name' => 'Empresa Mascara',
            'company_social_name' => 'Empresa Mascara LTDA',
            'company_document' => '12.345.678/0001-95',
            'company_email' => 'empresa-mascara@example.com',
            'company_phone' => '(11) 98888-7777',
            'owner_name' => 'Responsavel Mascara',
            'owner_email' => 'responsavel-mascara@example.com',
            'owner_document' => '977.794.741-00',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_code' => 'trimestral',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('empresa', [
            'email' => 'empresa-mascara@example.com',
            'cnpj_cpf' => '12345678000195',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'responsavel-mascara@example.com',
            'cpf' => '97779474100',
        ]);
    }

    public function test_self_service_rejects_duplicate_company_document_even_when_payload_is_masked(): void
    {
        EmpresaSubscriptionPlan::query()->updateOrCreate([
            'code' => 'trimestral',
        ], [
            'name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 59.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        Empresa::query()->create([
            'nome' => 'Empresa Existente',
            'fantasia' => 'Empresa Existente',
            'razaosocial' => 'Empresa Existente LTDA',
            'cnpj_cpf' => '12345678000195',
            'email' => 'empresa-existente@example.com',
            'fone' => '(11) 97777-6666',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'urlimagem' => '',
        ]);

        $this->from(route('self-service.register'))
            ->post(route('self-service.register.store'), [
                'company_name' => 'Empresa Duplicada',
                'company_social_name' => 'Empresa Duplicada LTDA',
                'company_document' => '12.345.678/0001-95',
                'company_email' => 'empresa-duplicada@example.com',
                'company_phone' => '(11) 98888-7777',
                'owner_name' => 'Responsavel Duplicado',
                'owner_email' => 'responsavel-duplicado@example.com',
                'owner_document' => '977.794.741-00',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'plan_code' => 'trimestral',
            ])
            ->assertRedirect(route('self-service.register'))
            ->assertSessionHasErrors('company_document');

        $this->assertDatabaseMissing('empresa', [
            'email' => 'empresa-duplicada@example.com',
        ]);
    }

    public function test_self_service_generates_initial_asaas_charge_with_trial_due_date(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers*' => Http::sequence()
                ->push(['data' => []], 200)
                ->push(['id' => 'cus_self_123'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_self_123',
                'customer' => 'cus_self_123',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 179.70,
                'dueDate' => now()->addDays(7)->format('Y-m-d'),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_self_123',
                'externalReference' => 'fin-self-123',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_self_123/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212SELF',
                'expirationDate' => now()->addDays(8)->toIso8601String(),
            ], 200),
        ]);

        $plan = EmpresaSubscriptionPlan::query()->updateOrCreate([
            'code' => 'trimestral',
        ], [
            'name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 59.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        $response = $this->post(route('self-service.register.store'), [
            'company_name' => 'Empresa Charge',
            'company_social_name' => 'Empresa Charge LTDA',
            'company_document' => '12.345.678/0001-95',
            'company_email' => 'empresa-charge@example.com',
            'company_phone' => '(11) 98888-7777',
            'owner_name' => 'Responsavel Charge',
            'owner_email' => 'responsavel-charge@example.com',
            'owner_document' => '97779474100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_code' => 'trimestral',
        ]);

        $empresa = Empresa::query()->where('email', 'empresa-charge@example.com')->firstOrFail();
        $subscription = EmpresaSubscription::query()->where('empresa_id', $empresa->id)->firstOrFail();

        $response->assertRedirect(route('self-service.subscription.pending'));

        $this->assertDatabaseHas('empresa_financeiro_configs', [
            'empresa_id' => $empresa->id,
            'asaas_customer_id' => 'cus_self_123',
        ]);

        $this->assertDatabaseHas('empresa_financeiro_cobrancas', [
            'empresa_id' => $empresa->id,
            'empresa_financeiro_config_id' => $empresa->financeiroConfig->id,
            'gateway_payment_id' => 'pay_self_123',
            'gateway_customer_id' => 'cus_self_123',
            'quantidade_dispositivos' => 1,
            'valor_total' => 179.70,
            'status' => 'PENDING',
        ]);

        $charge = $empresa->financeiroCobrancas()->latest('id')->firstOrFail();

        $this->assertSame(
            $subscription->trial_ends_at?->format('Y-m-d'),
            $charge->vencimento?->format('Y-m-d')
        );

        $this->actingAs(User::query()->where('email', 'responsavel-charge@example.com')->firstOrFail())
            ->get(route('self-service.subscription.pending'))
            ->assertOk()
            ->assertSee('Assinatura pendente')
            ->assertSee('Plano Trimestral')
            ->assertSee('000201010212SELF');

        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
            && (float) data_get($request->data(), 'value') === 179.7);

        $this->assertSame((int) $plan->id, (int) $subscription->subscription_plan_id);
    }

    public function test_self_service_registration_uses_admin_defined_default_permissions(): void
    {
        EmpresaSubscriptionPlan::query()->updateOrCreate([
            'code' => 'trimestral',
        ], [
            'name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 59.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        Configuracao::query()->create([
            'empresa_id' => null,
            'selfServiceDefaultMenuPermissions' => [
                User::MENU_PRODUTOS,
                User::MENU_CONFIG_TELA_WEB,
                User::MENU_ATIVAR_TV,
            ],
        ]);

        $this->post(route('self-service.register.store'), [
            'company_name' => 'Empresa Permissao Padrao',
            'company_social_name' => 'Empresa Permissao Padrao LTDA',
            'company_document' => '12.345.678/0001-95',
            'company_email' => 'empresa-permissao@example.com',
            'company_phone' => '(11) 98888-7777',
            'owner_name' => 'Responsavel Permissao',
            'owner_email' => 'responsavel-permissao@example.com',
            'owner_document' => '97779474100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_code' => 'trimestral',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'responsavel-permissao@example.com')->firstOrFail();

        $this->assertSame([
            User::MENU_PRODUTOS,
            User::MENU_CONFIG_TELA_WEB,
            User::MENU_ATIVAR_TV,
        ], $user->menu_permissions);
    }

    public function test_self_service_registration_applies_admin_defined_default_web_screen_model(): void
    {
        EmpresaSubscriptionPlan::query()->updateOrCreate([
            'code' => 'trimestral',
        ], [
            'name' => 'Plano Trimestral',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
            'valor_unitario' => 59.90,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        $model = WebScreenModel::query()->create([
            'empresa_id' => null,
            'nome' => 'Modelo Padrao TV',
            'is_admin_default' => true,
            'config_payload' => [
                'showTitle' => false,
                'titleText' => 'Modelo Padrao TV',
            ],
        ]);

        Configuracao::query()->create([
            'empresa_id' => null,
            'selfServiceDefaultWebScreenModelId' => $model->id,
        ]);

        $this->post(route('self-service.register.store'), [
            'company_name' => 'Empresa Modelo TV',
            'company_social_name' => 'Empresa Modelo TV LTDA',
            'company_document' => '12.345.678/0001-95',
            'company_email' => 'empresa-modelo-tv@example.com',
            'company_phone' => '(11) 98888-7777',
            'owner_name' => 'Responsavel Modelo TV',
            'owner_email' => 'responsavel-modelo-tv@example.com',
            'owner_document' => '97779474100',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan_code' => 'trimestral',
        ])->assertRedirect(route('dashboard', absolute: false));

        $empresa = Empresa::query()->where('email', 'empresa-modelo-tv@example.com')->firstOrFail();
        $config = Configuracao::query()->where('empresa_id', $empresa->id)->firstOrFail();

        $this->assertFalse((bool) $config->showTitle);
        $this->assertSame('Modelo Padrao TV', $config->titleText);
    }
}