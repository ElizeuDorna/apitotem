<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_carousel_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->string('button_label', 60)->nullable();
            $table->string('button_link', 1000)->nullable();
            $table->string('image_source_type', 20);
            $table->string('image_url', 1000)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_carousel_slides');
    }
};