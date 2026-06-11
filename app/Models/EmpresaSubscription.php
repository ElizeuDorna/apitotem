<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EmpresaSubscription extends Model
{
    use HasFactory;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'empresa_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'trial_ends_at',
        'access_expires_at',
        'grace_ends_at',
        'blocked_at',
        'blocked_reason',
        'plan_name',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'trial_ends_at' => 'date',
        'access_expires_at' => 'date',
        'grace_ends_at' => 'date',
        'blocked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_TRIAL,
            self::STATUS_ACTIVE,
            self::STATUS_EXPIRED,
            self::STATUS_BLOCKED,
            self::STATUS_CANCELLED,
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(EmpresaSubscriptionPlan::class, 'subscription_plan_id');
    }

    public function normalizedStatus(?Carbon $referenceDate = null): string
    {
        $referenceDate = ($referenceDate ?: now())->copy()->startOfDay();

        if ($this->blocked_at || $this->status === self::STATUS_BLOCKED) {
            return self::STATUS_BLOCKED;
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        if ($this->isExpiredAt($referenceDate) && ! $this->isInGracePeriod($referenceDate)) {
            return self::STATUS_EXPIRED;
        }

        if ($this->status === self::STATUS_TRIAL) {
            return self::STATUS_TRIAL;
        }

        return self::STATUS_ACTIVE;
    }

    public function isInGracePeriod(?Carbon $referenceDate = null): bool
    {
        if (! $this->grace_ends_at) {
            return false;
        }

        $referenceDate = ($referenceDate ?: now())->copy()->startOfDay();

        return ! $this->grace_ends_at->copy()->startOfDay()->lt($referenceDate);
    }

    public function isExpiredAt(?Carbon $referenceDate = null): bool
    {
        $referenceDate = ($referenceDate ?: now())->copy()->startOfDay();

        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->access_expires_at && $this->access_expires_at->copy()->startOfDay()->lt($referenceDate)) {
            return true;
        }

        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->copy()->startOfDay()->lt($referenceDate);
    }
}