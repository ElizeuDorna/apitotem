<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'departamento_id', 'empresa_id'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class,'grupo_id');
    }
     protected $hidden = [
                'departamento',
                'created_at',
                'updated_at',
            ];
}
