<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaFinanceiroCobranca extends Model
{
    use HasFactory;

    protected $table = 'empresa_financeiro_cobrancas';

    protected $fillable = [
        'empresa_id',
        'empresa_financeiro_config_id',
        'referencia',
        'descricao',
        'quantidade_dispositivos',
        'valor_unitario',
        'valor_total',
        'vencimento',
        'status',
        'payment_method',
        'gateway',
        'gateway_customer_id',
        'gateway_payment_id',
        'external_reference',
        'invoice_url',
        'pix_qr_code',
        'pix_copy_paste',
        'pix_expires_at',
        'paid_at',
        'last_gateway_sync_at',
        'gateway_payload',
    ];

    protected $casts = [
        'quantidade_dispositivos' => 'integer',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'vencimento' => 'date',
        'pix_expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_gateway_sync_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function financeiroConfig(): BelongsTo
    {
        return $this->belongsTo(EmpresaFinanceiroConfig::class, 'empresa_financeiro_config_id');
    }

    public static function awaitingPaymentStatuses(): array
    {
        return ['PENDING', 'OVERDUE', 'CONFIRMED'];
    }

    public function isAwaitingPayment(): bool
    {
        return in_array((string) $this->status, self::awaitingPaymentStatuses(), true);
    }

    public function isPaid(): bool
    {
        return in_array((string) $this->status, ['RECEIVED', 'RECEIVED_IN_CASH', 'CONFIRMED'], true);
    }

    public function statusLabel(): string
    {
        return match ((string) $this->status) {
            'RECEIVED', 'RECEIVED_IN_CASH' => 'Pago',
            'CONFIRMED' => 'Pago em analise',
            'OVERDUE' => 'Vencido',
            'PENDING' => 'Aguardando pagamento',
            'CANCELLED', 'DELETED' => 'Cancelado',
            'REFUNDED' => 'Estornado',
            'ERROR' => 'Erro na integracao',
            default => 'Em processamento',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ((string) $this->status) {
            'RECEIVED', 'RECEIVED_IN_CASH' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'CONFIRMED' => 'bg-blue-100 text-blue-800 border-blue-200',
            'OVERDUE' => 'bg-amber-100 text-amber-800 border-amber-200',
            'CANCELLED', 'DELETED', 'REFUNDED', 'ERROR' => 'bg-red-100 text-red-800 border-red-200',
            default => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }
}