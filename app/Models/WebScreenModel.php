<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebScreenModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nome',
        'config_payload',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'config_payload' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}