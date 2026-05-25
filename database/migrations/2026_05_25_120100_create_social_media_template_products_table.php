<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_template_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_media_template_id')->constrained('social_media_templates')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produto')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('custom_title', 160)->nullable();
            $table->string('custom_image_url', 500)->nullable();
            $table->boolean('show_price')->default(true);
            $table->boolean('show_offer_price')->default(true);
            $table->timestamps();

            $table->unique(['social_media_template_id', 'produto_id'], 'social_media_template_products_unique');
            $table->index(['social_media_template_id', 'sort_order'], 'social_media_template_products_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_template_products');
    }
};