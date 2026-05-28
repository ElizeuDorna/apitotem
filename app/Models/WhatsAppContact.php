<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppContact extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'empresa_id',
        'name',
        'whatsapp_number',
        'whatsapp_number_e164',
        'opt_in_whatsapp',
        'opt_in_whatsapp_at',
        'opt_in_source',
        'last_whatsapp_inbound_at',
        'status',
    ];

    protected $casts = [
        'opt_in_whatsapp' => 'boolean',
        'opt_in_whatsapp_at' => 'datetime',
        'last_whatsapp_inbound_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppCampaignRecipient::class, 'whatsapp_contact_id');
    }
}
