<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppIntegration extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_integrations';

    protected $fillable = [
        'empresa_id',
        'status',
        'meta_business_account_id',
        'meta_phone_number_id',
        'display_phone_number',
        'access_token',
        'access_token_expires_at',
        'last_synced_at',
        'last_error',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'access_token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(WhatsAppCampaign::class, 'whatsapp_integration_id');
    }
}
