<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaPublicPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'hero_title',
        'hero_subtitle',
        'about_title',
        'about_content',
        'contact_title',
        'contact_content',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'cta_label',
        'cta_link',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}