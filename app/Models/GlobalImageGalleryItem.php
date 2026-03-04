<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalImageGalleryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'global_image_gallery_id',
        'slot',
        'source_type',
        'external_url',
        'file_path',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(GlobalImageGallery::class, 'global_image_gallery_id');
    }
}
