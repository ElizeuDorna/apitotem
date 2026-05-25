<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'provider',
        'status',
        'instagram_user_id',
        'instagram_username',
        'instagram_business_account_id',
        'facebook_page_id',
        'facebook_page_name',
        'access_token',
        'access_token_expires_at',
        'last_synced_at',
        'last_error',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'access_token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}