<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->string('asaasBaseUrl')->nullable()->after('apiRefreshInterval');
            $table->text('asaasApiKey')->nullable()->after('asaasBaseUrl');
            $table->text('asaasWebhookToken')->nullable()->after('asaasApiKey');
        });

        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            $table->boolean('asaas_integration_ativa')->default(true)->after('cobranca_automatica_ativa');
            $table->boolean('bloquear_tv_inadimplencia')->default(false)->after('asaas_integration_ativa');
            $table->boolean('exibir_qr_code_tv_bloqueada')->default(false)->after('bloquear_tv_inadimplencia');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            $table->dropColumn([
                'asaas_integration_ativa',
                'bloquear_tv_inadimplencia',
                'exibir_qr_code_tv_bloqueada',
            ]);
        });

        Schema::table('configuracoes', function (Blueprint $table) {
            $table->dropColumn([
                'asaasBaseUrl',
                'asaasApiKey',
                'asaasWebhookToken',
            ]);
        });
    }
};