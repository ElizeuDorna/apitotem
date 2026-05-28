<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialMediaTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nome',
        'titulo',
        'legenda',
        'layout_mode',
        'cover_image_url',
        'image_publish_mode',
        'scheduled_start_at',
        'scheduled_end_at',
        'instagram_auto_publish',
        'facebook_auto_publish',
        'publish_to_instagram',
        'publish_to_facebook',
        'instagram_publish_status',
        'instagram_last_published_at',
        'instagram_last_error',
        'instagram_publish_id',
        'facebook_publish_status',
        'facebook_last_published_at',
        'facebook_last_error',
        'facebook_publish_id',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'instagram_auto_publish' => 'boolean',
        'facebook_auto_publish' => 'boolean',
        'publish_to_instagram' => 'boolean',
        'publish_to_facebook' => 'boolean',
        'instagram_last_published_at' => 'datetime',
        'facebook_last_published_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function templateProducts(): HasMany
    {
        return $this->hasMany(SocialMediaTemplateProduct::class)->orderBy('sort_order');
    }
}