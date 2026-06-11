<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmpresaSubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'empresa_subscription_plans';

    protected $fillable = [
        'code',
        'name',
        'description',
        'intervalo_cobranca_dias',
        'valor_unitario',
        'trial_days',
        'is_active',
        'is_self_service',
        'sort_order',
    ];

    protected $casts = [
        'intervalo_cobranca_dias' => 'integer',
        'valor_unitario' => 'decimal:2',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'is_self_service' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(EmpresaSubscription::class, 'subscription_plan_id');
    }
}