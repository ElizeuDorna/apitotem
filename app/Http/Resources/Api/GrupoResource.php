<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrupoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'empresa_id' => $this->empresa_id,
            'departamento_id' => $this->departamento_id,
            'departamento' => $this->whenLoaded('departamento', function () {
                return [
                    'id' => $this->departamento->id,
                    'nome' => $this->departamento->nome,
                ];
            }),
        ];
    }
}
