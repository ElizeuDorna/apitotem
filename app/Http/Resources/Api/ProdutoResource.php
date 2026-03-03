<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->CODIGO,
            'nome' => $this->NOME,
            'preco' => (float) $this->PRECO,
            'oferta' => $this->OFERTA !== null ? (float) $this->OFERTA : null,
            'imagem' => $this->IMG,
            'grupo' => $this->whenLoaded('grupo', function () {
                return [
                    'id' => $this->grupo->id,
                    'nome' => $this->grupo->nome,
                ];
            }),
            'departamento' => $this->whenLoaded('departamento', function () {
                return [
                    'id' => $this->departamento->id,
                    'nome' => $this->departamento->nome,
                ];
            }),
        ];
    }
}
