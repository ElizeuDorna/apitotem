<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\EmpresaSubscription;

class EmpresaSubscriptionService
{
    public function currentStatus(Empresa $empresa): array
    {
        if (! $empresa->isClienteFinal()) {
            return $this->defaultState('not_applicable');
        }

        $subscription = $empresa->subscription()->first();

        if (! $subscription) {
            return $this->defaultState('legacy_active');
        }

        $status = $subscription->normalizedStatus();

        if ($status === EmpresaSubscription::STATUS_BLOCKED) {
            return $this->blockedState(
                'subscription_blocked',
                $subscription->blocked_reason ?: 'Assinatura bloqueada para esta empresa.',
                $subscription,
                $status
            );
        }

        if ($status === EmpresaSubscription::STATUS_CANCELLED) {
            return $this->blockedState(
                'subscription_cancelled',
                'Assinatura cancelada para esta empresa.',
                $subscription,
                $status
            );
        }

        if ($status === EmpresaSubscription::STATUS_EXPIRED && ! $subscription->isInGracePeriod()) {
            return $this->blockedState(
                'subscription_expired',
                'Assinatura expirada. Regularize a empresa para liberar o acesso.',
                $subscription,
                $status
            );
        }

        return [
            'blocked' => false,
            'reason' => null,
            'message' => null,
            'charge' => null,
            'status' => $subscription->isInGracePeriod() ? 'grace' : $status,
            'subscription' => $this->serializeSubscription($subscription, $status),
        ];
    }

    public function canUseTv(Empresa $empresa): bool
    {
        return ! $this->currentStatus($empresa)['blocked'];
    }

    public function canUseAdmin(Empresa $empresa): bool
    {
        $state = $this->currentStatus($empresa);

        return ! in_array($state['status'] ?? null, [EmpresaSubscription::STATUS_BLOCKED, EmpresaSubscription::STATUS_CANCELLED], true);
    }

    private function defaultState(string $status): array
    {
        return [
            'blocked' => false,
            'reason' => null,
            'message' => null,
            'charge' => null,
            'status' => $status,
            'subscription' => null,
        ];
    }

    private function blockedState(string $reason, string $message, EmpresaSubscription $subscription, string $status): array
    {
        return [
            'blocked' => true,
            'reason' => $reason,
            'message' => $message,
            'charge' => null,
            'status' => $status,
            'subscription' => $this->serializeSubscription($subscription, $status),
        ];
    }

    private function serializeSubscription(EmpresaSubscription $subscription, string $status): array
    {
        return [
            'id' => $subscription->id,
            'status' => $status,
            'plan_name' => $subscription->plan_name,
            'starts_at' => optional($subscription->starts_at)?->format('Y-m-d'),
            'trial_ends_at' => optional($subscription->trial_ends_at)?->format('Y-m-d'),
            'access_expires_at' => optional($subscription->access_expires_at)?->format('Y-m-d'),
            'grace_ends_at' => optional($subscription->grace_ends_at)?->format('Y-m-d'),
            'blocked_at' => optional($subscription->blocked_at)?->toIso8601String(),
            'blocked_reason' => $subscription->blocked_reason,
        ];
    }
}