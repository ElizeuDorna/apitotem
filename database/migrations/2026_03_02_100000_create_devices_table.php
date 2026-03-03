<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->string('nome');
            $table->string('local')->nullable();
            $table->string('token', 100)->unique();
            $table->string('device_uuid', 100)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
