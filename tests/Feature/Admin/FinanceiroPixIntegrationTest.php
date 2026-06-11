<?php

namespace Tests\Feature\Admin;

use App\Models\Device;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaFinanceiroConfig;
use App\Models\EmpresaSubscription;
use App\Models\EmpresaSubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinanceiroPixIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_generate_pix_charge_for_direct_client_final(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers*' => Http::sequence()
                ->push(['data' => []], 200)
                ->push(['id' => 'cus_123'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_123',
                'customer' => 'cus_123',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 59.8,
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_123',
                'externalReference' => 'fin-ref-123',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_123/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212',
                'expirationDate' => now()->addDays(1)->toIso8601String(),
            ], 200),
        ]);

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Pix',
            'cnpj_cpf' => '12345678901',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
            'email' => 'cliente@example.com',
            'fone' => '62999999999',
        ]);

        EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 29.90,
            'valor_receber_unitario' => 29.90,
            'data_vencimento' => now()->addDays(3)->toDateString(),
            'data_aviso' => now()->addDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
        ]);

        $this->createDevice($cliente, 'Token 1', 'uuid-1');
        $this->createDevice($cliente, 'Token 2', 'uuid-2');
        $this->createDevice($cliente, 'Token 3', 'uuid-3', false);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.financeiro.charges.store', $cliente), [
            'description' => 'Mensalidade Cliente Pix - 06/2026',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        $response
            ->assertRedirect(route('admin.financeiro.show', $cliente))
            ->assertSessionHas('success', 'Cobranca PIX gerada com sucesso.');

        $this->assertDatabaseHas('empresa_financeiro_configs', [
            'empresa_id' => $cliente->id,
            'asaas_customer_id' => 'cus_123',
        ]);

        $this->assertDatabaseHas('empresa_financeiro_cobrancas', [
            'empresa_id' => $cliente->id,
            'gateway_payment_id' => 'pay_123',
            'gateway_customer_id' => 'cus_123',
            'quantidade_dispositivos' => 2,
            'valor_total' => 59.80,
            'status' => 'PENDING',
            'payment_method' => 'PIX',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
            && (float) data_get($request->data(), 'value') === 59.8);
    }

    public function test_asaas_webhook_updates_existing_charge_status(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');
        config()->set('services.asaas.webhook_token', 'webhook-secret');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/payments/pay_123/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212',
                'expirationDate' => now()->addDays(1)->toIso8601String(),
            ], 200),
        ]);

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Webhook',
            'cnpj_cpf' => '98765432100',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ]);

        $plan = EmpresaSubscriptionPlan::query()->create([
            'code' => 'mensal-webhook',
            'name' => 'Plano Mensal Webhook',
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
            'valor_unitario' => 25,
            'trial_days' => 7,
            'is_active' => true,
            'is_self_service' => true,
            'sort_order' => 10,
        ]);

        $config = EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 25,
            'valor_receber_unitario' => 25,
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
        ]);

        $subscription = EmpresaSubscription::query()->create([
            'empresa_id' => $cliente->id,
            'subscription_plan_id' => $plan->id,
            'status' => EmpresaSubscription::STATUS_TRIAL,
            'starts_at' => now()->subDay()->toDateString(),
            'trial_ends_at' => now()->addDays(2)->toDateString(),
            'access_expires_at' => now()->addDays(2)->toDateString(),
            'plan_name' => $plan->name,
            'metadata' => ['plan_code' => $plan->code],
        ]);

        $cobranca = EmpresaFinanceiroCobranca::query()->create([
            'empresa_id' => $cliente->id,
            'empresa_financeiro_config_id' => $config->id,
            'referencia' => '202606',
            'descricao' => 'Mensalidade Cliente Webhook',
            'quantidade_dispositivos' => 1,
            'valor_unitario' => 25,
            'valor_total' => 25,
            'vencimento' => now()->addDays(2)->toDateString(),
            'status' => 'PENDING',
            'payment_method' => 'PIX',
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_123',
            'external_reference' => 'fin-ref-456',
        ]);

        $this->postJson(route('asaas.webhook.receive'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_123',
                'customer' => 'cus_123',
                'status' => 'RECEIVED',
                'billingType' => 'PIX',
                'value' => 25,
                'dueDate' => now()->addDays(2)->format('Y-m-d'),
                'paymentDate' => now()->toIso8601String(),
                'externalReference' => 'fin-ref-456',
            ],
        ], [
            'asaas-access-token' => 'webhook-secret',
        ])->assertOk();

        $cobranca->refresh();
        $subscription->refresh();

        $this->assertSame('RECEIVED', $cobranca->status);
        $this->assertNotNull($cobranca->paid_at);
        $this->assertSame('000201010212', $cobranca->pix_copy_paste);
        $this->assertSame(EmpresaSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNull($subscription->trial_ends_at);
        $this->assertNotNull($subscription->access_expires_at);
        $this->assertSame('pay_123', data_get($subscription->metadata, 'last_paid_charge_id'));
    }

    public function test_asaas_webhook_requires_configured_token(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');
        config()->set('services.asaas.webhook_token', null);

        $this->postJson(route('asaas.webhook.receive'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_123',
            ],
        ])->assertStatus(503);
    }

    public function test_second_pix_request_reuses_existing_open_charge(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers*' => Http::sequence()
                ->push(['data' => []], 200)
                ->push(['id' => 'cus_123'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_123',
                'customer' => 'cus_123',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 29.9,
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_123',
                'externalReference' => 'fin-ref-123',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_123' => Http::response([
                'id' => 'pay_123',
                'customer' => 'cus_123',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 29.9,
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_123',
                'externalReference' => 'fin-ref-123',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_123/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212',
                'expirationDate' => now()->addDays(1)->toIso8601String(),
            ], 200),
        ]);

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Pix Reuso',
            'cnpj_cpf' => '12345678901',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 29.90,
            'valor_receber_unitario' => 29.90,
            'data_vencimento' => now()->addDays(3)->toDateString(),
            'data_aviso' => now()->addDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
        ]);

        $this->createDevice($cliente, 'Token 1', 'uuid-r1');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)->post(route('admin.financeiro.charges.store', $cliente), [
            'description' => 'Mensalidade Cliente Pix - 06/2026',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ])->assertRedirect(route('admin.financeiro.show', $cliente));

        $this->actingAs($admin)->post(route('admin.financeiro.charges.store', $cliente), [
            'description' => 'Mensalidade Cliente Pix - 06/2026',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ])
            ->assertRedirect(route('admin.financeiro.show', $cliente))
            ->assertSessionHas('success', 'Ja existe uma cobranca PIX em aberto. O status foi atualizado.');

        $this->assertSame(1, EmpresaFinanceiroCobranca::query()->where('empresa_id', $cliente->id)->count());
    }

    public function test_second_pix_request_does_not_duplicate_charge_while_first_is_processing(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake();

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Pix Processando',
            'cnpj_cpf' => '12345678901',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        $config = EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 29.90,
            'valor_receber_unitario' => 29.90,
            'data_vencimento' => now()->addDays(3)->toDateString(),
            'data_aviso' => now()->addDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
        ]);

        EmpresaFinanceiroCobranca::query()->create([
            'empresa_id' => $cliente->id,
            'empresa_financeiro_config_id' => $config->id,
            'referencia' => '202606',
            'descricao' => 'Mensalidade em processamento',
            'quantidade_dispositivos' => 1,
            'valor_unitario' => 29.90,
            'valor_total' => 29.90,
            'vencimento' => now()->addDays(2)->toDateString(),
            'status' => 'PENDING',
            'payment_method' => 'PIX',
            'gateway' => 'asaas',
            'external_reference' => 'fin-ref-processing',
        ]);

        $this->createDevice($cliente, 'Token 1', 'uuid-processing');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)->post(route('admin.financeiro.charges.store', $cliente), [
            'description' => 'Mensalidade Cliente Pix - 06/2026',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ])
            ->assertRedirect(route('admin.financeiro.show', $cliente))
            ->assertSessionHas('success', 'Ja existe uma cobranca PIX em processamento. Aguarde alguns instantes antes de tentar novamente.');

        $this->assertSame(1, EmpresaFinanceiroCobranca::query()->where('empresa_id', $cliente->id)->count());
        Http::assertNothingSent();
    }

    public function test_financeiro_show_marks_deleted_gateway_charge_and_shows_new_pix_form(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/payments/pay_deleted' => Http::response([
                'id' => 'pay_deleted',
                'customer' => 'cus_deleted',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 29.9,
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'deleted' => true,
                'externalReference' => 'fin-ref-deleted',
            ], 200),
        ]);

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Cobranca Excluida',
            'cnpj_cpf' => '12345678904',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
            'email' => 'cliente.deleted@example.com',
            'fone' => '62999999998',
        ]);

        $config = EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 29.90,
            'valor_receber_unitario' => 29.90,
            'data_vencimento' => now()->addDays(3)->toDateString(),
            'data_aviso' => now()->addDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
        ]);

        $cobranca = EmpresaFinanceiroCobranca::query()->create([
            'empresa_id' => $cliente->id,
            'empresa_financeiro_config_id' => $config->id,
            'referencia' => '202606',
            'descricao' => 'Mensalidade removida no Asaas',
            'quantidade_dispositivos' => 1,
            'valor_unitario' => 29.90,
            'valor_total' => 29.90,
            'vencimento' => now()->addDays(2)->toDateString(),
            'status' => 'PENDING',
            'payment_method' => 'PIX',
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_deleted',
            'external_reference' => 'fin-ref-deleted',
        ]);

        $this->createDevice($cliente, 'Token deleted', 'uuid-deleted');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.financeiro.show', $cliente))
            ->assertOk()
            ->assertSee('Gerar cobrança PIX', false);

        $cobranca->refresh();

        $this->assertSame('DELETED', $cobranca->status);
    }

    public function test_admin_can_configure_billing_interval_for_company(): void
    {
        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Intervalo',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)->put(route('admin.financeiro.update', $cliente), [
            'valor_receber_unitario' => 59.90,
            'data_vencimento' => now()->addDays(30)->toDateString(),
            'data_aviso' => now()->addDays(25)->toDateString(),
            'data_bloqueio' => now()->addDays(35)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_180_DIAS,
        ])
            ->assertRedirect(route('admin.financeiro.show', $cliente))
            ->assertSessionHas('success', 'Valores financeiros atualizados com sucesso.');

        $this->assertDatabaseHas('empresa_financeiro_configs', [
            'empresa_id' => $cliente->id,
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_180_DIAS,
            'cobranca_automatica_ativa' => false,
            'valor_receber_unitario' => 59.90,
        ]);
    }

    public function test_admin_can_enable_automatic_billing_for_company(): void
    {
        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Auto',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)->put(route('admin.financeiro.update', $cliente), [
            'valor_receber_unitario' => 49.90,
            'data_vencimento' => now()->toDateString(),
            'data_aviso' => now()->subDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
            'cobranca_automatica_ativa' => 1,
        ])->assertRedirect(route('admin.financeiro.show', $cliente));

        $this->assertDatabaseHas('empresa_financeiro_configs', [
            'empresa_id' => $cliente->id,
            'cobranca_automatica_ativa' => true,
        ]);
    }

    public function test_pix_charge_uses_configured_interval_when_due_date_is_omitted(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        $vencimentoAnterior = now()->subDays(5)->startOfDay();
        $vencimentoEsperado = $vencimentoAnterior->copy()->addDays(EmpresaFinanceiroConfig::INTERVALO_90_DIAS);

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers*' => Http::sequence()
                ->push(['data' => []], 200)
                ->push(['id' => 'cus_90'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_90',
                'customer' => 'cus_90',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 29.9,
                'dueDate' => $vencimentoEsperado->format('Y-m-d'),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_90',
                'externalReference' => 'fin-ref-90',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_90/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212',
                'expirationDate' => now()->addDays(1)->toIso8601String(),
            ], 200),
        ]);

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Intervalo 90',
            'cnpj_cpf' => '12345678901',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        $config = EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 29.90,
            'valor_receber_unitario' => 29.90,
            'data_vencimento' => now()->subDays(95)->toDateString(),
            'data_aviso' => now()->subDays(96)->toDateString(),
            'data_bloqueio' => now()->subDays(90)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
        ]);

        EmpresaFinanceiroCobranca::query()->create([
            'empresa_id' => $cliente->id,
            'empresa_financeiro_config_id' => $config->id,
            'referencia' => '202601',
            'descricao' => 'Mensalidade anterior',
            'quantidade_dispositivos' => 1,
            'valor_unitario' => 29.90,
            'valor_total' => 29.90,
            'vencimento' => $vencimentoAnterior->format('Y-m-d'),
            'status' => 'RECEIVED',
            'payment_method' => 'PIX',
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_old_90',
            'external_reference' => 'fin-ref-old-90',
        ]);

        $this->createDevice($cliente, 'Token 1', 'uuid-i90');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)->post(route('admin.financeiro.charges.store', $cliente), [
            'description' => 'Mensalidade Intervalo 90',
        ])
            ->assertRedirect(route('admin.financeiro.show', $cliente))
            ->assertSessionHas('success', 'Cobranca PIX gerada com sucesso.');

        $this->assertDatabaseHas('empresa_financeiro_cobrancas', [
            'empresa_id' => $cliente->id,
            'gateway_payment_id' => 'pay_90',
            'vencimento' => $vencimentoEsperado->format('Y-m-d'),
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
            && data_get($request->data(), 'dueDate') === $vencimentoEsperado->format('Y-m-d'));
    }

    public function test_pix_charge_total_scales_with_configured_billing_cycle(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers*' => Http::sequence()
                ->push(['data' => []], 200)
                ->push(['id' => 'cus_180'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_180',
                'customer' => 'cus_180',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 240.0,
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_180',
                'externalReference' => 'fin-ref-180',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_180/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212',
                'expirationDate' => now()->addDays(1)->toIso8601String(),
            ], 200),
        ]);

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Intervalo 180',
            'cnpj_cpf' => '12345678901',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
            'email' => 'cliente180@example.com',
            'fone' => '62999999998',
        ]);

        EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 20.00,
            'valor_receber_unitario' => 20.00,
            'data_vencimento' => now()->addDays(3)->toDateString(),
            'data_aviso' => now()->addDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_180_DIAS,
        ]);

        $this->createDevice($cliente, 'Token 1', 'uuid-180-1');
        $this->createDevice($cliente, 'Token 2', 'uuid-180-2');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin)->post(route('admin.financeiro.charges.store', $cliente), [
            'description' => 'Cobranca ciclo Cliente 180',
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ])->assertRedirect(route('admin.financeiro.show', $cliente));

        $this->assertDatabaseHas('empresa_financeiro_cobrancas', [
            'empresa_id' => $cliente->id,
            'gateway_payment_id' => 'pay_180',
            'quantidade_dispositivos' => 2,
            'valor_unitario' => 20.00,
            'valor_total' => 240.00,
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://api-sandbox.asaas.com/v3/payments'
            && (float) data_get($request->data(), 'value') === 240.0);
    }

    public function test_recurring_charge_command_generates_only_for_auto_enabled_companies(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/customers*' => Http::sequence()
                ->push(['data' => []], 200)
                ->push(['id' => 'cus_auto'], 200),
            'https://api-sandbox.asaas.com/v3/payments' => Http::response([
                'id' => 'pay_auto',
                'customer' => 'cus_auto',
                'status' => 'PENDING',
                'billingType' => 'PIX',
                'value' => 39.9,
                'dueDate' => now()->startOfDay()->format('Y-m-d'),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_auto',
                'externalReference' => 'fin-ref-auto',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_auto/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212',
                'expirationDate' => now()->addDays(1)->toIso8601String(),
            ], 200),
        ]);

        $clienteAuto = $this->createEmpresa([
            'nome' => 'Cliente Auto Scheduler',
            'cnpj_cpf' => '12345678901',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        $clienteManual = $this->createEmpresa([
            'nome' => 'Cliente Manual Scheduler',
            'cnpj_cpf' => '12345678902',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $clienteAuto->id,
            'valor_pagar_unitario' => 39.90,
            'valor_receber_unitario' => 39.90,
            'data_vencimento' => now()->toDateString(),
            'data_aviso' => now()->subDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
            'cobranca_automatica_ativa' => true,
        ]);

        EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $clienteManual->id,
            'valor_pagar_unitario' => 39.90,
            'valor_receber_unitario' => 39.90,
            'data_vencimento' => now()->toDateString(),
            'data_aviso' => now()->subDay()->toDateString(),
            'data_bloqueio' => now()->addDays(5)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
            'cobranca_automatica_ativa' => false,
        ]);

        $this->createDevice($clienteAuto, 'Token auto', 'uuid-auto');
        $this->createDevice($clienteManual, 'Token manual', 'uuid-manual');

        $this->artisan('financeiro:dispatch-recurring-charges')
            ->expectsOutput('1 cobranca(s) automatica(s) gerada(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('empresa_financeiro_cobrancas', [
            'empresa_id' => $clienteAuto->id,
            'gateway_payment_id' => 'pay_auto',
        ]);

        $this->assertDatabaseMissing('empresa_financeiro_cobrancas', [
            'empresa_id' => $clienteManual->id,
        ]);
    }

    public function test_pending_charge_sync_command_updates_paid_status_automatically(): void
    {
        config()->set('services.asaas.api_key', 'sandbox-key');
        config()->set('services.asaas.base_url', 'https://api-sandbox.asaas.com/v3');

        Http::fake([
            'https://api-sandbox.asaas.com/v3/payments/pay_sync' => Http::response([
                'id' => 'pay_sync',
                'customer' => 'cus_sync',
                'status' => 'RECEIVED',
                'billingType' => 'PIX',
                'value' => 39.9,
                'dueDate' => now()->subDay()->format('Y-m-d'),
                'paymentDate' => now()->toIso8601String(),
                'invoiceUrl' => 'https://asaas.test/invoice/pay_sync',
                'externalReference' => 'fin-ref-sync',
            ], 200),
            'https://api-sandbox.asaas.com/v3/payments/pay_sync/pixQrCode' => Http::response([
                'encodedImage' => base64_encode('fake-image'),
                'payload' => '000201010212',
                'expirationDate' => now()->addDay()->toIso8601String(),
            ], 200),
        ]);

        $cliente = $this->createEmpresa([
            'nome' => 'Cliente Sync Automatico',
            'cnpj_cpf' => '12345678903',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => null,
        ]);

        $config = EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $cliente->id,
            'valor_pagar_unitario' => 39.90,
            'valor_receber_unitario' => 39.90,
            'data_vencimento' => now()->subDays(2)->toDateString(),
            'data_aviso' => now()->subDays(3)->toDateString(),
            'data_bloqueio' => now()->addDays(3)->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
        ]);

        EmpresaFinanceiroCobranca::query()->create([
            'empresa_id' => $cliente->id,
            'empresa_financeiro_config_id' => $config->id,
            'referencia' => '202606',
            'descricao' => 'Mensalidade aguardando webhook',
            'quantidade_dispositivos' => 1,
            'valor_unitario' => 39.90,
            'valor_total' => 39.90,
            'vencimento' => now()->subDay()->toDateString(),
            'status' => 'PENDING',
            'payment_method' => 'PIX',
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_sync',
            'external_reference' => 'fin-ref-sync',
        ]);

        $this->artisan('financeiro:sync-pending-charges')
            ->expectsOutput('1 cobranca(s) consultada(s); 1 atualizada(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('empresa_financeiro_cobrancas', [
            'empresa_id' => $cliente->id,
            'gateway_payment_id' => 'pay_sync',
            'status' => 'RECEIVED',
        ]);

        $cobranca = EmpresaFinanceiroCobranca::query()->where('gateway_payment_id', 'pay_sync')->firstOrFail();

        $this->assertNotNull($cobranca->paid_at);
        $this->assertNotNull($cobranca->last_gateway_sync_at);
    }

    private function createEmpresa(array $overrides = []): Empresa
    {
        return Empresa::query()->create(array_merge([
            'codigo' => 'EMP'.random_int(1000, 9999),
            'nome' => 'Empresa Teste',
            'fantasia' => 'Empresa Teste',
            'razaosocial' => 'Empresa Teste LTDA',
            'cnpj_cpf' => '11111111111',
            'urlimagem' => 'logo.png',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ], $overrides));
    }

    private function createDevice(Empresa $empresa, string $token, string $uuid, bool $ativo = true): Device
    {
        return Device::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'TV Teste '.$uuid,
            'local' => 'Recepcao',
            'token' => $token,
            'device_uuid' => $uuid,
            'ativo' => $ativo,
        ]);
    }
}