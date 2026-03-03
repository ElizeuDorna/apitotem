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
            // Drop existing foreign keys
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['grupo_id']);
            
            // Drop and recreate columns as NOT NULL
            $table->dropColumn(['departamento_id', 'grupo_id']);
        });

        Schema::table('produto', function (Blueprint $table) {
            // Add back as NOT NULL with foreign key constraints
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
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

        Schema::table('produto', function (Blueprint $table) {
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
        });
    }
};
