<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalImageGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(GlobalImageGalleryItem::class)->orderBy('slot');
    }
}
