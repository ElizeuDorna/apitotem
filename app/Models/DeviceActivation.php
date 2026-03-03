<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceActivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_uuid',
        'code',
        'expires_at',
        'activated',
        'device_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'activated' => 'boolean',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
