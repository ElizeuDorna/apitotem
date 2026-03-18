<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_financeiro_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresa')->cascadeOnDelete();
            $table->decimal('valor_pagar_unitario', 12, 2)->default(0);
            $table->decimal('valor_receber_unitario', 12, 2)->default(0);
            $table->timestamps();

            $table->index('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_financeiro_configs');
    }
};
