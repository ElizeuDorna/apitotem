<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'web_config_payload',
        'atualizar_produtos_segundos',
        'volume',
        'orientacao',
    ];

    protected $casts = [
        'web_config_payload' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
