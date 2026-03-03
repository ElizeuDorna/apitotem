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
        Schema::table('produto', function (Blueprint $table) {
            // add new columns to link departamento and grupo
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produto', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['grupo_id']);
            $table->dropColumn(['departamento_id', 'grupo_id']);
        });
    }
};
