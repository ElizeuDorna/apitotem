<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaAutomationPublication extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'social_media_automation_setting_id',
        'social_media_template_id',
        'produto_id',
        'mode',
        'status',
        'batch_key',
        'dedupe_key',
        'error_message',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(SocialMediaAutomationSetting::class, 'social_media_automation_setting_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SocialMediaTemplate::class, 'social_media_template_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
