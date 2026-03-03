<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'empresa_id'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class,'departamento_id');
    }
    protected $hidden = [
                
                'created_at',
                'updated_at',
            ];
    
}
