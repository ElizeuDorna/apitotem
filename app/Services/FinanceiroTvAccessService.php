<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaFinanceiroConfig;

class FinanceiroTvAccessService
{
    public function __construct(private readonly EmpresaSubscriptionService $subscriptionService) {}

    public function resolveTvBlockState(Empresa $empresa): array
    {
        if ((int) $empresa->nivel_acesso !== Empresa::NIVEL_CLIENTE_FINAL) {
            return $this->defaultState();
        }

        $subscriptionState = $this->subscriptionService->currentStatus($empresa);

        if ($subscriptionState['blocked']) {
            return $subscriptionState;
        }

        $config = $empresa->financeiroConfig()->first();

        if (! $config || ! $config->bloquear_tv_inadimplencia) {
            return $this->defaultState();
        }

        if (! $this->blockDateReached($config)) {
            return $this->defaultState();
        }

        if (! $config->asaas_integration_ativa) {
            return $this->resolveInternalBlockState($config);
        }

        $cobranca = EmpresaFinanceiroCobranca::query()
            ->where('empresa_id', $empresa->id)
            ->whereIn('status', ['PENDING', 'OVERDUE'])
            ->latest('vencimento')
            ->latest('id')
            ->first();

        if (! $cobranca) {
            return $this->defaultState();
        }

        $qrCode = null;
        if ($config->exibir_qr_code_tv_bloqueada && $cobranca->pix_qr_code) {
            $qrCode = str_starts_with((string) $cobranca->pix_qr_code, 'data:')
                ? (string) $cobranca->pix_qr_code
                : 'data:image/png;base64,'.(string) $cobranca->pix_qr_code;
        }

        return [
            'blocked' => true,
            'reason' => 'financeiro_blocked',
            'message' => 'TV bloqueada por inadimplencia. A liberacao ocorre automaticamente apos a confirmacao do pagamento.',
            'charge' => [
                'id' => $cobranca->id,
                'descricao' => $cobranca->descricao,
                'status' => $cobranca->status,
                'status_label' => $cobranca->statusLabel(),
                'valor_total' => (float) $cobranca->valor_total,
                'vencimento' => optional($cobranca->vencimento)?->format('Y-m-d'),
                'invoice_url' => $cobranca->invoice_url,
                'pix_copy_paste' => $config->exibir_qr_code_tv_bloqueada ? $cobranca->pix_copy_paste : null,
                'pix_qr_code' => $qrCode,
                'show_qr_code' => (bool) $config->exibir_qr_code_tv_bloqueada,
            ],
        ];
    }

    private function resolveInternalBlockState(EmpresaFinanceiroConfig $config): array
    {
        if (! $this->blockDateReached($config)) {
            return $this->defaultState();
        }

        return [
            'blocked' => true,
            'reason' => 'financeiro_blocked_internal',
            'message' => 'TV bloqueada pela data de bloqueio configurada. Regularize manualmente ou ajuste o ciclo financeiro para liberar o acesso.',
            'charge' => null,
        ];
    }

    private function blockDateReached(EmpresaFinanceiroConfig $config): bool
    {
        return $config->data_bloqueio !== null
            && ! $config->data_bloqueio->gt(now()->startOfDay());
    }

    private function defaultState(): array
    {
        return [
            'blocked' => false,
            'reason' => null,
            'message' => null,
            'charge' => null,
        ];
    }
}