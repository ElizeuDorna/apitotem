<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produto', function (Blueprint $table) {
            if (! Schema::hasColumn('produto', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresa')->cascadeOnDelete();
            }
        });

        DB::statement("\n            UPDATE produto p\n            LEFT JOIN departamentos d ON d.id = p.departamento_id\n            LEFT JOIN grupos g ON g.id = p.grupo_id\n            LEFT JOIN empresa e ON\n                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(e.cnpj_cpf, '.', ''), '/', ''), '-', ''), '(', ''), ')', '') =\n                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(p.cnpj_cpf, '.', ''), '/', ''), '-', ''), '(', ''), ')', '')\n            SET p.empresa_id = COALESCE(d.empresa_id, g.empresa_id, e.id)\n            WHERE p.empresa_id IS NULL\n        ");

        Schema::table('produto', function (Blueprint $table) {
            $table->index('empresa_id', 'produto_empresa_id_idx');
            $table->index('grupo_id', 'produto_grupo_id_idx');
            $table->index('departamento_id', 'produto_departamento_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('produto', function (Blueprint $table) {
            $table->dropIndex('produto_empresa_id_idx');
            $table->dropIndex('produto_grupo_id_idx');
            $table->dropIndex('produto_departamento_id_idx');

            if (Schema::hasColumn('produto', 'empresa_id')) {
                $table->dropConstrainedForeignId('empresa_id');
            }
        });
    }
};
