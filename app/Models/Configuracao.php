<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    use HasFactory;

    protected $table = 'configuracoes';

    protected $fillable = [
        'empresa_id',
        'apiUrl',
        'apiRefreshInterval',
        'priceColor',
        'offerColor',
        'rowBackgroundColor',
        'borderColor',
        'appBackgroundColor',
        'mainBorderColor',
        'gradientStartColor',
        'gradientEndColor',
        'useGradient',
        'gradientStop1',
        'gradientStop2',
        'showBorder',
        'isMainBorderEnabled',
        'showImage',
        'imageSize',
        'isPaginationEnabled',
        'pageSize',
        'paginationInterval',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    protected $casts = [
        'useGradient' => 'boolean',
        'showBorder' => 'boolean',
        'isMainBorderEnabled' => 'boolean',
        'showImage' => 'boolean',
        'isPaginationEnabled' => 'boolean',
        'gradientStop1' => 'float',
        'gradientStop2' => 'float',
        'apiRefreshInterval' => 'integer',
        'imageSize' => 'integer',
        'pageSize' => 'integer',
        'paginationInterval' => 'integer',
    ];
}
