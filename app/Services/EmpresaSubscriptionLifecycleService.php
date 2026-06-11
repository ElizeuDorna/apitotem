<?php

namespace App\Services;

use App\Models\EmpresaFinanceiroCobranca;
use App\Models\EmpresaSubscription;
use Illuminate\Support\Carbon;

class EmpresaSubscriptionLifecycleService
{
    public function syncFromPaidCharge(EmpresaFinanceiroCobranca $charge): ?EmpresaSubscription
    {
        if (! $charge->isPaid()) {
            return null;
        }

        $empresa = $charge->relationLoaded('empresa') ? $charge->empresa : $charge->empresa()->first();

        if (! $empresa || ! $empresa->isClienteFinal()) {
            return null;
        }

        $subscription = $empresa->subscription()->first();

        if (! $subscription) {
            return null;
        }

        $metadata = is_array($subscription->metadata) ? $subscription->metadata : [];
        $paymentId = (string) ($charge->gateway_payment_id ?: $charge->id);

        if (($metadata['last_paid_charge_id'] ?? null) === $paymentId) {
            return $subscription;
        }

        $config = $empresa->financeiroConfig()->first();
        $intervalDays = $config?->billingIntervalDays() ?? 30;
        $baseDate = $charge->paid_at?->copy()->startOfDay() ?? now()->startOfDay();
        $expiresAt = $baseDate->copy()->addDays($intervalDays);

        $metadata['last_paid_charge_id'] = $paymentId;
        $metadata['last_paid_at'] = optional($charge->paid_at)->toIso8601String();
        $metadata['last_charge_reference'] = $charge->referencia;

        $subscription->forceFill([
            'status' => EmpresaSubscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'access_expires_at' => $expiresAt->toDateString(),
            'grace_ends_at' => null,
            'blocked_at' => null,
            'blocked_reason' => null,
            'metadata' => $metadata,
        ])->save();

        return $subscription->fresh();
    }
}