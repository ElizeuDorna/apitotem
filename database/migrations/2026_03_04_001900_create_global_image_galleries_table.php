<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_image_galleries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 14)->unique();
            $table->string('name', 255);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_image_galleries');
    }
};
