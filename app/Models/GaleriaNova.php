<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GaleriaNova extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'source_type',
        'external_url',
        'file_path',
        'image_hash',
        'created_by',
    ];
}
