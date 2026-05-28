<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppCampaign extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_campaigns';

    protected $fillable = [
        'empresa_id',
        'whatsapp_integration_id',
        'name',
        'message_type',
        'body_text',
        'media_url',
        'meta_template_name',
        'template_language_code',
        'scheduled_at',
        'status',
        'last_processed_at',
        'last_error',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'last_processed_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(WhatsAppIntegration::class, 'whatsapp_integration_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignRecipient::class, 'whatsapp_campaign_id');
    }
}
