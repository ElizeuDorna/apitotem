<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            $table->date('data_aviso')->nullable()->after('data_vencimento');
            $table->date('data_bloqueio')->nullable()->after('data_aviso');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_financeiro_configs', function (Blueprint $table) {
            $table->dropColumn(['data_aviso', 'data_bloqueio']);
        });
    }
};
