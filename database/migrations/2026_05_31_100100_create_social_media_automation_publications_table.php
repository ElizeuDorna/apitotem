<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_automation_publications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('social_media_automation_setting_id')->nullable();
            $table->unsignedBigInteger('social_media_template_id')->nullable();
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->string('mode', 30);
            $table->string('status', 30)->default('published');
            $table->string('batch_key', 160)->nullable();
            $table->string('dedupe_key', 190);
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id', 'smap_empresa_fk')->references('id')->on('empresa')->cascadeOnDelete();
            $table->foreign('social_media_automation_setting_id', 'smap_setting_fk')->references('id')->on('social_media_automation_settings')->nullOnDelete();
            $table->foreign('social_media_template_id', 'smap_template_fk')->references('id')->on('social_media_templates')->nullOnDelete();
            $table->foreign('produto_id', 'smap_produto_fk')->references('id')->on('produto')->nullOnDelete();

            $table->unique(['empresa_id', 'mode', 'dedupe_key'], 'social_media_automation_publications_dedupe_unique');
            $table->index(['empresa_id', 'published_at'], 'social_media_automation_publications_empresa_published_idx');
            $table->index(['batch_key', 'status'], 'social_media_automation_publications_batch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_automation_publications');
    }
};
