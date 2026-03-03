<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_activations', function (Blueprint $table) {
            $table->id();
            $table->string('device_uuid', 100);
            $table->string('code', 5);
            $table->timestamp('expires_at');
            $table->boolean('activated')->default(false);
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->timestamps();

            $table->index(['device_uuid', 'activated']);
            $table->index(['code', 'activated']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_activations');
    }
};
