<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('titulo', 160)->nullable();
            $table->text('legenda')->nullable();
            $table->string('layout_mode', 30)->default('product_list');
            $table->string('cover_image_url', 500)->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->boolean('instagram_auto_publish')->default(false);
            $table->string('instagram_publish_status', 30)->default('draft');
            $table->timestamp('instagram_last_published_at')->nullable();
            $table->text('instagram_last_error')->nullable();
            $table->string('instagram_publish_id', 120)->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
            $table->index(['empresa_id', 'instagram_publish_status']);
            $table->index(['scheduled_start_at', 'scheduled_end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_templates');
    }
};