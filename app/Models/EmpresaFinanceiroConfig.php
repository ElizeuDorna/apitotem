<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaFinanceiroConfig extends Model
{
    use HasFactory;

    protected $table = 'empresa_financeiro_configs';

    protected $fillable = [
        'empresa_id',
        'valor_pagar_unitario',
        'valor_receber_unitario',
        'data_vencimento',
        'data_aviso',
        'data_bloqueio',
    ];

    protected $casts = [
        'valor_pagar_unitario' => 'decimal:2',
        'valor_receber_unitario' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_aviso' => 'date',
        'data_bloqueio' => 'date',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
