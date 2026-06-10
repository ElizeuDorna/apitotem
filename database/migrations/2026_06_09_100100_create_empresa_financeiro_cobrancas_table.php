<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_financeiro_cobrancas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('empresa_financeiro_config_id')->nullable();
            $table->string('referencia', 120)->nullable();
            $table->string('descricao', 255);
            $table->unsignedInteger('quantidade_dispositivos')->default(0);
            $table->decimal('valor_unitario', 12, 2)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->date('vencimento');
            $table->string('status', 40)->default('PENDING');
            $table->string('payment_method', 20)->default('PIX');
            $table->string('gateway', 30)->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->string('external_reference')->nullable()->unique();
            $table->text('invoice_url')->nullable();
            $table->longText('pix_qr_code')->nullable();
            $table->longText('pix_copy_paste')->nullable();
            $table->timestamp('pix_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_gateway_sync_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'vencimento']);
            $table->foreign('empresa_id', 'efc_empresa_fk')->references('id')->on('empresa')->cascadeOnDelete();
            $table->foreign('empresa_financeiro_config_id', 'efc_config_fk')->references('id')->on('empresa_financeiro_configs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_financeiro_cobrancas');
    }
};