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
        'web_screen_model_id',
        'product_department_id',
        'product_group_id',
        'web_config_payload',
        'atualizar_produtos_segundos',
        'volume',
        'orientacao',
    ];

    protected $casts = [
        'web_screen_model_id' => 'integer',
        'product_department_id' => 'integer',
        'product_group_id' => 'integer',
        'web_config_payload' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function webScreenModel(): BelongsTo
    {
        return $this->belongsTo(WebScreenModel::class, 'web_screen_model_id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'product_department_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'product_group_id');
    }
}
