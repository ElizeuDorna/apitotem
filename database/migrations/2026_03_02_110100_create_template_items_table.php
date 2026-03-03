<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->unsignedInteger('ordem')->default(1);
            $table->text('conteudo')->nullable();
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'ordem']);
            $table->index(['template_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_items');
    }
};
