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
        // Drop codigo column from departamentos and grupos
        if (Schema::hasTable('departamentos')) {
            Schema::table('departamentos', function (Blueprint $table) {
                if (Schema::hasColumn('departamentos', 'codigo')) {
                    $table->dropUnique(['codigo']);
                    $table->dropColumn('codigo');
                }
            });
        }

        if (Schema::hasTable('grupos')) {
            Schema::table('grupos', function (Blueprint $table) {
                if (Schema::hasColumn('grupos', 'codigo')) {
                    $table->dropUnique(['codigo']);
                    $table->dropColumn('codigo');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversing this migration is not necessary - codigo was removed intentionally
    }
};
