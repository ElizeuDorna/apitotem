<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nome',
        'tipo_layout',
        'web_config_payload',
        'is_default_web',
    ];

    protected $casts = [
        'web_config_payload' => 'array',
        'is_default_web' => 'boolean',
    ];

    public const LAYOUTS = [
        'grade',
        'lista',
        'video_background',
        'promocao',
        'misto',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TemplateItem::class)->orderBy('ordem');
    }

    public function deviceConfigurations(): HasMany
    {
        return $this->hasMany(DeviceConfiguration::class);
    }
}
