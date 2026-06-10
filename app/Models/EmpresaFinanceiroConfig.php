<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaFinanceiroConfig extends Model
{
    use HasFactory;

    public const INTERVALO_30_DIAS = 30;

    public const INTERVALO_90_DIAS = 90;

    public const INTERVALO_180_DIAS = 180;

    public const INTERVALO_1_ANO = 365;

    protected $table = 'empresa_financeiro_configs';

    protected $fillable = [
        'empresa_id',
        'valor_pagar_unitario',
        'valor_receber_unitario',
        'data_vencimento',
        'data_aviso',
        'data_bloqueio',
        'intervalo_cobranca_dias',
        'cobranca_automatica_ativa',
        'asaas_integration_ativa',
        'bloquear_tv_inadimplencia',
        'exibir_qr_code_tv_bloqueada',
        'asaas_customer_id',
    ];

    protected $casts = [
        'valor_pagar_unitario' => 'decimal:2',
        'valor_receber_unitario' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_aviso' => 'date',
        'data_bloqueio' => 'date',
        'intervalo_cobranca_dias' => 'integer',
        'cobranca_automatica_ativa' => 'boolean',
        'asaas_integration_ativa' => 'boolean',
        'bloquear_tv_inadimplencia' => 'boolean',
        'exibir_qr_code_tv_bloqueada' => 'boolean',
    ];

    public static function billingIntervalOptions(): array
    {
        return [
            self::INTERVALO_30_DIAS => 'Mensal (30 dias)',
            self::INTERVALO_90_DIAS => 'Trimestral (90 dias)',
            self::INTERVALO_180_DIAS => 'Semestral (180 dias)',
            self::INTERVALO_1_ANO => 'Anual (1 ano)',
        ];
    }

    public function billingIntervalDays(): int
    {
        $intervalo = (int) ($this->intervalo_cobranca_dias ?: self::INTERVALO_30_DIAS);

        return array_key_exists($intervalo, self::billingIntervalOptions())
            ? $intervalo
            : self::INTERVALO_30_DIAS;
    }

    public function billingIntervalLabel(): string
    {
        return self::billingIntervalOptions()[$this->billingIntervalDays()];
    }

    public function automaticBillingStatusLabel(): string
    {
        return $this->cobranca_automatica_ativa ? 'Ativado' : 'Desativado';
    }

    public function asaasIntegrationStatusLabel(): string
    {
        return $this->asaas_integration_ativa ? 'Ativada' : 'Desativada';
    }

    public function tvBlockStatusLabel(): string
    {
        return $this->bloquear_tv_inadimplencia ? 'Ativado' : 'Desativado';
    }

    public function tvQrStatusLabel(): string
    {
        return $this->exibir_qr_code_tv_bloqueada ? 'Ativado' : 'Desativado';
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
