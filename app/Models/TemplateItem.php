<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'tipo',
        'ordem',
        'conteudo',
        'config_json',
    ];

    protected $casts = [
        'config_json' => 'array',
    ];

    public const TIPOS = [
        'produto_lista',
        'imagem',
        'video',
        'banner',
        'texto',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
