<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->unique()->constrained('devices')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->unsignedInteger('atualizar_produtos_segundos')->default(30);
            $table->unsignedTinyInteger('volume')->default(50);
            $table->string('orientacao', 20)->default('landscape');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_configurations');
    }
};
