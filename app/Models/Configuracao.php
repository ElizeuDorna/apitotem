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
        'videoUrl',
        'videoMuted',
        'videoPlaylist',
        'showVideoPanel',
        'priceColor',
        'offerColor',
        'rowBackgroundColor',
        'borderColor',
        'productsPanelBackgroundColor',
        'listBorderColor',
        'videoBackgroundColor',
        'appBackgroundColor',
        'mainBorderColor',
        'gradientStartColor',
        'gradientEndColor',
        'backgroundImageUrl',
        'useGradient',
        'gradientStop1',
        'gradientStop2',
        'showBorder',
        'isRowBorderTransparent',
        'showTitle',
        'showBackgroundImage',
        'isProductsPanelTransparent',
        'isListBorderTransparent',
        'isMainBorderEnabled',
        'showImage',
        'imageSize',
        'imageWidth',
        'imageHeight',
        'listFontSize',
        'groupLabelFontSize',
        'groupLabelColor',
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
        'videoMuted' => 'boolean',
        'videoPlaylist' => 'array',
        'showVideoPanel' => 'boolean',
        'showBorder' => 'boolean',
        'isRowBorderTransparent' => 'boolean',
        'showTitle' => 'boolean',
        'showBackgroundImage' => 'boolean',
        'isProductsPanelTransparent' => 'boolean',
        'isListBorderTransparent' => 'boolean',
        'isMainBorderEnabled' => 'boolean',
        'showImage' => 'boolean',
        'isPaginationEnabled' => 'boolean',
        'gradientStop1' => 'float',
        'gradientStop2' => 'float',
        'apiRefreshInterval' => 'integer',
        'imageSize' => 'integer',
        'imageWidth' => 'integer',
        'imageHeight' => 'integer',
        'listFontSize' => 'integer',
        'groupLabelFontSize' => 'integer',
        'pageSize' => 'integer',
        'paginationInterval' => 'integer',
    ];
}
