<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebScreenModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nome',
        'is_admin_default',
        'source_model_id',
        'config_payload',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'is_admin_default' => 'boolean',
        'source_model_id' => 'integer',
        'config_payload' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function sourceModel(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_model_id');
    }

    public function clones(): HasMany
    {
        return $this->hasMany(self::class, 'source_model_id');
    }

    public function deviceConfigurations(): HasMany
    {
        return $this->hasMany(DeviceConfiguration::class, 'web_screen_model_id');
    }
}