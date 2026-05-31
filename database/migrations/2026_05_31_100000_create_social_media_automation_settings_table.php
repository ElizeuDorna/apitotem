<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_automation_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('mode', 30)->default('daily_offers');
            $table->boolean('publish_to_instagram')->default(true);
            $table->boolean('publish_to_facebook')->default(false);
            $table->json('publish_times')->nullable();
            $table->unsignedInteger('max_products_per_post')->default(10);
            $table->boolean('require_image')->default(true);
            $table->unsignedInteger('republish_after_hours')->default(24);
            $table->string('title_prefix', 160)->nullable();
            $table->text('caption_prefix')->nullable();
            $table->timestamps();

            $table->unique('empresa_id', 'social_media_automation_settings_empresa_unique');
            $table->index(['enabled', 'mode'], 'social_media_automation_settings_enabled_mode_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_automation_settings');
    }
};
