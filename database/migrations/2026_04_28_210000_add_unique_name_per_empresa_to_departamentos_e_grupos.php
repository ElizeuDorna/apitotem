<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateDepartamentos = DB::table('departamentos')
            ->select('empresa_id', 'nome', DB::raw('COUNT(*) as total'))
            ->whereNotNull('empresa_id')
            ->groupBy('empresa_id', 'nome')
            ->having('total', '>', 1)
            ->exists();

        if ($duplicateDepartamentos) {
            throw new RuntimeException('Existem departamentos duplicados para a mesma empresa. Corrija os dados antes de aplicar a migration.');
        }

        $duplicateGrupos = DB::table('grupos')
            ->select('empresa_id', 'nome', DB::raw('COUNT(*) as total'))
            ->whereNotNull('empresa_id')
            ->groupBy('empresa_id', 'nome')
            ->having('total', '>', 1)
            ->exists();

        if ($duplicateGrupos) {
            throw new RuntimeException('Existem grupos duplicados para a mesma empresa. Corrija os dados antes de aplicar a migration.');
        }

        Schema::table('departamentos', function ($table) {
            $table->unique(['empresa_id', 'nome'], 'departamentos_empresa_nome_unique');
        });

        Schema::table('grupos', function ($table) {
            $table->unique(['empresa_id', 'nome'], 'grupos_empresa_nome_unique');
        });
    }

    public function down(): void
    {
        Schema::table('departamentos', function ($table) {
            $table->dropUnique('departamentos_empresa_nome_unique');
        });

        Schema::table('grupos', function ($table) {
            $table->dropUnique('grupos_empresa_nome_unique');
        });
    }
};