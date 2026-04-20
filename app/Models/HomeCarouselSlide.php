<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeCarouselSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'title',
        'subtitle',
        'button_label',
        'button_link',
        'image_source_type',
        'image_url',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function resolvedImageUrl(): ?string
    {
        if ($this->image_source_type === 'link') {
            return $this->image_url;
        }

        if ($this->image_source_type === 'upload' && $this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        return null;
    }
}