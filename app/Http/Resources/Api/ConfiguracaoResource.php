<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresa_id,
            'apiUrl' => $this->apiUrl,
            'apiRefreshInterval' => $this->apiRefreshInterval,
            'priceColor' => $this->priceColor,
            'offerColor' => $this->offerColor,
            'rowBackgroundColor' => $this->rowBackgroundColor,
            'borderColor' => $this->borderColor,
            'appBackgroundColor' => $this->appBackgroundColor,
            'mainBorderColor' => $this->mainBorderColor,
            'gradientStartColor' => $this->gradientStartColor,
            'gradientEndColor' => $this->gradientEndColor,
            'useGradient' => $this->useGradient,
            'gradientStop1' => $this->gradientStop1,
            'gradientStop2' => $this->gradientStop2,
            'showBorder' => $this->showBorder,
            'isMainBorderEnabled' => $this->isMainBorderEnabled,
            'showImage' => $this->showImage,
            'imageSize' => $this->imageSize,
            'isPaginationEnabled' => $this->isPaginationEnabled,
            'pageSize' => $this->pageSize,
            'paginationInterval' => $this->paginationInterval,
        ];
    }
}
