<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('departamentos', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresa')->nullOnDelete();
            }
        });

        Schema::table('grupos', function (Blueprint $table) {
            if (! Schema::hasColumn('grupos', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresa')->nullOnDelete();
            }
        });

        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'empresa_id')) {
                $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresa')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            if (Schema::hasColumn('departamentos', 'empresa_id')) {
                $table->dropConstrainedForeignId('empresa_id');
            }
        });

        Schema::table('grupos', function (Blueprint $table) {
            if (Schema::hasColumn('grupos', 'empresa_id')) {
                $table->dropConstrainedForeignId('empresa_id');
            }
        });

        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'empresa_id')) {
                $table->dropConstrainedForeignId('empresa_id');
            }
        });
    }
};
