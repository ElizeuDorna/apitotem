<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaAutomationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'enabled',
        'mode',
        'publish_to_instagram',
        'publish_to_facebook',
        'publish_times',
        'max_products_per_post',
        'require_image',
        'republish_after_hours',
        'title_prefix',
        'caption_prefix',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'publish_to_instagram' => 'boolean',
        'publish_to_facebook' => 'boolean',
        'publish_times' => 'array',
        'max_products_per_post' => 'integer',
        'require_image' => 'boolean',
        'republish_after_hours' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
