<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Empresa;
use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaFinanceiroConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class FinanceiroChargeService
{
    public function createPixChargeForEmpresa(Empresa $empresa, AsaasService $asaas, ?string $description = null, Carbon|string|null $dueDate = null, ?int $quantityOverride = null): array
    {
        $normalizedDueDate = $this->normalizeDueDate($dueDate);

        $result = DB::transaction(function () use ($empresa, $description, $normalizedDueDate, $quantityOverride) {
            Empresa::query()->whereKey($empresa->id)->lockForUpdate()->firstOrFail();

            $config = EmpresaFinanceiroConfig::query()->firstOrCreate(
                ['empresa_id' => $empresa->id],
                [
                    'valor_pagar_unitario' => 0,
                    'valor_receber_unitario' => 0,
                    'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
                    'cobranca_automatica_ativa' => false,
                    'asaas_integration_ativa' => true,
                    'bloquear_tv_inadimplencia' => false,
                    'exibir_qr_code_tv_bloqueada' => false,
                ]
            );

            if (! $config->asaas_integration_ativa) {
                throw new RuntimeException('A integracao com o Asaas nao esta ativada para esta empresa.');
            }

            $existing = EmpresaFinanceiroCobranca::query()
                ->where('empresa_id', $empresa->id)
                ->whereIn('status', EmpresaFinanceiroCobranca::awaitingPaymentStatuses())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return [
                    'status' => $existing->gateway_payment_id ? 'existing' : 'processing',
                    'charge' => $existing,
                    'config' => $config,
                ];
            }

            $quantidadeDispositivos = $quantityOverride !== null
                ? max(1, $quantityOverride)
                : $this->countActiveDevicesForEmpresa((int) $empresa->id);
            $valorUnitario = (float) ($config->valor_receber_unitario ?? 0);
            $valorTotal = round($quantidadeDispositivos * $config->billingCycleUnitTotal(), 2);

            if ($quantidadeDispositivos <= 0) {
                throw new RuntimeException('Nao ha dispositivos ativos para gerar cobranca.');
            }

            if ($valorTotal <= 0) {
                throw new RuntimeException('Configure um valor unitario maior que zero antes de gerar a cobranca PIX.');
            }

            $cobranca = EmpresaFinanceiroCobranca::query()->create([
                'empresa_id' => $empresa->id,
                'empresa_financeiro_config_id' => $config->id,
                'referencia' => now()->format('Ym'),
                'descricao' => $description ?: 'Mensalidade '.$empresa->nome.' - '.now()->format('m/Y'),
                'quantidade_dispositivos' => $quantidadeDispositivos,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'vencimento' => ($normalizedDueDate ?: $this->resolveSuggestedChargeDueDate($empresa, $config))->format('Y-m-d'),
                'status' => 'PENDING',
                'payment_method' => 'PIX',
                'gateway' => 'asaas',
                'external_reference' => 'fin-'.Str::uuid(),
            ]);

            return [
                'status' => 'created',
                'charge' => $cobranca,
                'config' => $config,
            ];
        }, 3);

        if ($result['status'] !== 'created') {
            return $result;
        }

        $cobranca = $result['charge'];

        try {
            $result['charge'] = $asaas->createPixCharge($empresa, $result['config'], $cobranca);
        } catch (RuntimeException $exception) {
            $cobranca->status = 'ERROR';
            $cobranca->gateway_payload = ['error' => $exception->getMessage()];
            $cobranca->save();

            throw $exception;
        }

        return $result;
    }

    public function dispatchScheduledPixCharges(AsaasService $asaas): int
    {
        $processed = 0;

        EmpresaFinanceiroConfig::query()
            ->where('cobranca_automatica_ativa', true)
            ->where('asaas_integration_ativa', true)
            ->with('empresa')
            ->orderBy('id')
            ->lazy()
            ->each(function (EmpresaFinanceiroConfig $config) use (&$processed, $asaas) {
                $empresa = $config->empresa;

                if (! $empresa || (int) $empresa->nivel_acesso !== Empresa::NIVEL_CLIENTE_FINAL) {
                    return;
                }

                $vencimentoSugerido = $this->resolveSuggestedChargeDueDate($empresa, $config);
                if ($vencimentoSugerido->gt(now()->startOfDay())) {
                    return;
                }

                try {
                    $result = $this->createPixChargeForEmpresa(
                        $empresa,
                        $asaas,
                        'Mensalidade '.$empresa->nome.' - '.$vencimentoSugerido->format('m/Y'),
                        $vencimentoSugerido
                    );

                    if ($result['status'] === 'created') {
                        $processed++;
                    }
                } catch (RuntimeException $exception) {
                    Log::warning('Falha ao gerar cobranca automatica.', [
                        'empresa_id' => $empresa->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });

        return $processed;
    }

    public function syncAwaitingPixCharges(AsaasService $asaas, int $limit = 100): array
    {
        $processed = 0;
        $updated = 0;

        EmpresaFinanceiroCobranca::query()
            ->where('gateway', 'asaas')
            ->whereNotNull('gateway_payment_id')
            ->whereIn('status', EmpresaFinanceiroCobranca::awaitingPaymentStatuses())
            ->where(function ($query) {
                $query->whereNull('last_gateway_sync_at')
                    ->orWhere('last_gateway_sync_at', '<=', now()->subMinutes(5));
            })
            ->orderBy('last_gateway_sync_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (EmpresaFinanceiroCobranca $cobranca) use (&$processed, &$updated, $asaas) {
                $statusAnterior = (string) $cobranca->status;
                $paidAtAnterior = optional($cobranca->paid_at)?->toDateTimeString();

                try {
                    $cobrancaAtualizada = $asaas->syncCharge($cobranca);
                    $processed++;

                    if (
                        (string) $cobrancaAtualizada->status !== $statusAnterior
                        || optional($cobrancaAtualizada->paid_at)?->toDateTimeString() !== $paidAtAnterior
                    ) {
                        $updated++;
                    }
                } catch (RuntimeException $exception) {
                    Log::warning('Falha ao sincronizar cobranca pendente no Asaas.', [
                        'cobranca_id' => $cobranca->id,
                        'empresa_id' => $cobranca->empresa_id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });

        return [
            'processed' => $processed,
            'updated' => $updated,
        ];
    }

    public function resolveSuggestedChargeDueDate(Empresa $empresa, EmpresaFinanceiroConfig $config): Carbon
    {
        $intervaloDias = $config->billingIntervalDays();

        $ultimoVencimento = EmpresaFinanceiroCobranca::query()
            ->where('empresa_id', $empresa->id)
            ->whereNotNull('vencimento')
            ->latest('vencimento')
            ->value('vencimento');

        if ($ultimoVencimento) {
            return Carbon::parse((string) $ultimoVencimento)->startOfDay()->addDays($intervaloDias);
        }

        if ($config->data_vencimento) {
            $proximoVencimento = $config->data_vencimento->copy()->startOfDay();

            while ($proximoVencimento->lt(now()->startOfDay())) {
                $proximoVencimento->addDays($intervaloDias);
            }

            return $proximoVencimento;
        }

        return now()->startOfDay()->addDays($intervaloDias);
    }

    private function countActiveDevicesForEmpresa(int $empresaId): int
    {
        return (int) Device::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->count();
    }

    private function normalizeDueDate(Carbon|string|null $dueDate): ?Carbon
    {
        if ($dueDate === null || $dueDate === '') {
            return null;
        }

        return $dueDate instanceof Carbon
            ? $dueDate->copy()->startOfDay()
            : Carbon::parse($dueDate)->startOfDay();
    }
}