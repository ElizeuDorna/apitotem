<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('tipo_layout', 40);
            $table->timestamps();

            $table->index(['empresa_id', 'tipo_layout']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
