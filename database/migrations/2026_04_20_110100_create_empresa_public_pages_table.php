<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_public_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->unique();
            $table->string('hero_title', 180)->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('about_title', 180)->nullable();
            $table->text('about_content')->nullable();
            $table->string('contact_title', 180)->nullable();
            $table->text('contact_content')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_whatsapp', 50)->nullable();
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_link', 1000)->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresa')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_public_pages');
    }
};