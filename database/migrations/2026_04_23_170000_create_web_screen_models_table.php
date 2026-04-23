<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_screen_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->string('nome');
            $table->json('config_payload')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_screen_models');
    }
};