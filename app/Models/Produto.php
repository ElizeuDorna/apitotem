<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $table = 'produto';

    protected $fillable = [
        'CODIGO',
        'NOME',
        'cnpj_cpf',
        'empresa_id',
        'PRECO',
        'OFERTA',
        'IMG',
        'departamento_id',
        'grupo_id'
    ];

    protected $casts = [
        'PRECO' => 'decimal:2',
        'OFERTA' => 'decimal:2',
    ];
       
    /**
     * Use CODIGO como chave de rota ao invés de ID
     */
    public function getRouteKeyName()
    {
        return 'CODIGO';
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

     protected $hidden = [
                'id',
                'created_at',
                'updated_at'
               
                
                
            ];
}