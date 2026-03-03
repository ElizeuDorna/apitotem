<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nome',
        'local',
        'token',
        'device_uuid',
        'ativo',
        'last_seen_at',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(DeviceActivation::class);
    }

    public function configuration(): HasOne
    {
        return $this->hasOne(DeviceConfiguration::class);
    }
}
