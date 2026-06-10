<?php

use App\Models\EmpresaFinanceiroConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('empresa_financeiro_configs', 'intervalo_cobranca_dias')) {
                $table->unsignedSmallInteger('intervalo_cobranca_dias')
                    ->default(EmpresaFinanceiroConfig::INTERVALO_30_DIAS)
                    ->after('data_bloqueio');
            }

            if (! Schema::hasColumn('empresa_financeiro_configs', 'cobranca_automatica_ativa')) {
                $table->boolean('cobranca_automatica_ativa')
                    ->default(false)
                    ->after('intervalo_cobranca_dias');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_financeiro_configs', 'intervalo_cobranca_dias')) {
                $table->dropColumn('intervalo_cobranca_dias');
            }

            if (Schema::hasColumn('empresa_financeiro_configs', 'cobranca_automatica_ativa')) {
                $table->dropColumn('cobranca_automatica_ativa');
            }
        });
    }
};