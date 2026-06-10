<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            if (! Schema::hasColumn('empresa_financeiro_configs', 'asaas_customer_id')) {
                $table->string('asaas_customer_id')->nullable()->after('data_bloqueio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_financeiro_configs', 'asaas_customer_id')) {
                $table->dropColumn('asaas_customer_id');
            }
        });
    }
};