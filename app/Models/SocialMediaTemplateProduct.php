<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaTemplateProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_media_template_id',
        'produto_id',
        'sort_order',
        'custom_title',
        'custom_image_url',
        'show_price',
        'show_offer_price',
    ];

    protected $casts = [
        'show_price' => 'boolean',
        'show_offer_price' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SocialMediaTemplate::class, 'social_media_template_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}