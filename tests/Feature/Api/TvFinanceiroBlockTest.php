<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaFinanceiroConfig;
use App\Models\EmpresaSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TvFinanceiroBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_tv_products_endpoint_blocks_and_then_releases_after_payment_confirmation(): void
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP9001',
            'nome' => 'Cliente TV Financeiro',
            'fantasia' => 'Cliente TV Financeiro',
            'razaosocial' => 'Cliente TV Financeiro LTDA',
            'cnpj_cpf' => '12345678000190',
            'urlimagem' => 'logo.png',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ]);

        $device = Device::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'TV Caixa',
            'local' => 'Caixa',
            'token' => 'tv-token-financeiro',
            'device_uuid' => 'tv-uuid-financeiro',
            'ativo' => true,
        ]);

        $config = EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $empresa->id,
            'valor_pagar_unitario' => 39.90,
            'valor_receber_unitario' => 39.90,
            'data_vencimento' => now()->subDays(10)->toDateString(),
            'data_aviso' => now()->subDays(9)->toDateString(),
            'data_bloqueio' => now()->subDay()->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
            'asaas_integration_ativa' => true,
            'bloquear_tv_inadimplencia' => true,
            'exibir_qr_code_tv_bloqueada' => true,
        ]);

        $cobranca = EmpresaFinanceiroCobranca::query()->create([
            'empresa_id' => $empresa->id,
            'empresa_financeiro_config_id' => $config->id,
            'referencia' => '202606',
            'descricao' => 'Mensalidade TV',
            'quantidade_dispositivos' => 1,
            'valor_unitario' => 39.90,
            'valor_total' => 39.90,
            'vencimento' => now()->subDay()->toDateString(),
            'status' => 'PENDING',
            'payment_method' => 'PIX',
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_tv_block',
            'invoice_url' => 'https://asaas.test/invoice/pay_tv_block',
            'pix_qr_code' => base64_encode('fake-image'),
            'pix_copy_paste' => '000201010212',
            'external_reference' => 'fin-tv-block',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/produtos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('financeiro.blocked', true)
            ->assertJsonPath('financeiro.charge.show_qr_code', true)
            ->assertJsonPath('financeiro.charge.pix_copy_paste', '000201010212')
            ->assertJsonPath('financeiro.charge.invoice_url', 'https://asaas.test/invoice/pay_tv_block');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/totemweb/config')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('financeiro.blocked', true);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/bootstrap')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('financeiro.blocked', true);

        $cobranca->status = 'RECEIVED';
        $cobranca->paid_at = now();
        $cobranca->save();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/produtos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('financeiro.blocked', false);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/bootstrap')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('financeiro.blocked', false);
    }

    public function test_tv_blocks_by_internal_block_date_when_asaas_integration_is_disabled(): void
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP9002',
            'nome' => 'Cliente TV Interno',
            'fantasia' => 'Cliente TV Interno',
            'razaosocial' => 'Cliente TV Interno LTDA',
            'cnpj_cpf' => '12345678000191',
            'urlimagem' => 'logo.png',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ]);

        $device = Device::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'TV Interna',
            'local' => 'Recepcao',
            'token' => 'tv-token-interno',
            'device_uuid' => 'tv-uuid-interno',
            'ativo' => true,
        ]);

        EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $empresa->id,
            'valor_pagar_unitario' => 39.90,
            'valor_receber_unitario' => 39.90,
            'data_vencimento' => now()->subDays(10)->toDateString(),
            'data_aviso' => now()->subDays(5)->toDateString(),
            'data_bloqueio' => now()->subDay()->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
            'asaas_integration_ativa' => false,
            'bloquear_tv_inadimplencia' => true,
            'exibir_qr_code_tv_bloqueada' => true,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/produtos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('financeiro.blocked', true)
            ->assertJsonPath('financeiro.reason', 'financeiro_blocked_internal')
            ->assertJsonPath('financeiro.charge', null);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/bootstrap')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('financeiro.blocked', true)
            ->assertJsonPath('financeiro.reason', 'financeiro_blocked_internal');
    }

    public function test_tv_shows_qr_when_block_date_is_reached_even_if_charge_due_date_is_in_future(): void
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP9003',
            'nome' => 'Cliente TV QR',
            'fantasia' => 'Cliente TV QR',
            'razaosocial' => 'Cliente TV QR LTDA',
            'cnpj_cpf' => '12345678000192',
            'urlimagem' => 'logo.png',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ]);

        $device = Device::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'TV QR',
            'local' => 'Loja',
            'token' => 'tv-token-qr',
            'device_uuid' => 'tv-uuid-qr',
            'ativo' => true,
        ]);

        $config = EmpresaFinanceiroConfig::query()->create([
            'empresa_id' => $empresa->id,
            'valor_pagar_unitario' => 39.90,
            'valor_receber_unitario' => 39.90,
            'data_vencimento' => now()->subDays(10)->toDateString(),
            'data_aviso' => now()->subDays(5)->toDateString(),
            'data_bloqueio' => now()->subDay()->toDateString(),
            'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
            'asaas_integration_ativa' => true,
            'bloquear_tv_inadimplencia' => true,
            'exibir_qr_code_tv_bloqueada' => true,
        ]);

        EmpresaFinanceiroCobranca::query()->create([
            'empresa_id' => $empresa->id,
            'empresa_financeiro_config_id' => $config->id,
            'referencia' => '202607',
            'descricao' => 'Mensalidade Julho',
            'quantidade_dispositivos' => 1,
            'valor_unitario' => 39.90,
            'valor_total' => 39.90,
            'vencimento' => now()->addDays(2)->toDateString(),
            'status' => 'PENDING',
            'payment_method' => 'PIX',
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_tv_qr',
            'invoice_url' => 'https://asaas.test/invoice/pay_tv_qr',
            'pix_qr_code' => base64_encode('fake-image'),
            'pix_copy_paste' => '000201010212999',
            'external_reference' => 'fin-tv-qr',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/produtos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('financeiro.blocked', true)
            ->assertJsonPath('financeiro.charge.show_qr_code', true)
            ->assertJsonPath('financeiro.charge.invoice_url', 'https://asaas.test/invoice/pay_tv_qr')
            ->assertJsonPath('financeiro.charge.pix_copy_paste', '000201010212999');
    }

    public function test_tv_blocks_when_empresa_subscription_is_expired_even_without_financeiro_block(): void
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP9004',
            'nome' => 'Cliente TV Assinatura Expirada',
            'fantasia' => 'Cliente TV Assinatura Expirada',
            'razaosocial' => 'Cliente TV Assinatura Expirada LTDA',
            'cnpj_cpf' => '12345678000193',
            'urlimagem' => 'logo.png',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ]);

        $device = Device::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'TV Assinatura',
            'local' => 'Recepcao',
            'token' => 'tv-token-assinatura-expirada',
            'device_uuid' => 'tv-uuid-assinatura-expirada',
            'ativo' => true,
        ]);

        EmpresaSubscription::query()->create([
            'empresa_id' => $empresa->id,
            'status' => EmpresaSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subMonths(2)->toDateString(),
            'access_expires_at' => now()->subDay()->toDateString(),
            'plan_name' => 'Plano Semestral',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/produtos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('financeiro.blocked', true)
            ->assertJsonPath('financeiro.reason', 'subscription_expired')
            ->assertJsonPath('financeiro.charge', null);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/bootstrap')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('financeiro.blocked', true)
            ->assertJsonPath('financeiro.reason', 'subscription_expired');
    }

    public function test_tv_keeps_access_during_subscription_grace_period(): void
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP9005',
            'nome' => 'Cliente TV Carencia',
            'fantasia' => 'Cliente TV Carencia',
            'razaosocial' => 'Cliente TV Carencia LTDA',
            'cnpj_cpf' => '12345678000194',
            'urlimagem' => 'logo.png',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
        ]);

        $device = Device::query()->create([
            'empresa_id' => $empresa->id,
            'nome' => 'TV Carencia',
            'local' => 'Loja',
            'token' => 'tv-token-carencia',
            'device_uuid' => 'tv-uuid-carencia',
            'ativo' => true,
        ]);

        EmpresaSubscription::query()->create([
            'empresa_id' => $empresa->id,
            'status' => EmpresaSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subMonths(2)->toDateString(),
            'access_expires_at' => now()->subDay()->toDateString(),
            'grace_ends_at' => now()->addDays(3)->toDateString(),
            'plan_name' => 'Plano Trial Migrado',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$device->token,
            'Accept' => 'application/json',
        ])->getJson('/api/tv/bootstrap')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('financeiro.blocked', false);
    }
}