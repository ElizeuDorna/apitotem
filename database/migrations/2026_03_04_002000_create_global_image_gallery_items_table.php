<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_image_gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_image_gallery_id')->constrained('global_image_galleries')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot');
            $table->string('source_type', 10);
            $table->string('external_url', 1000)->nullable();
            $table->string('file_path', 1000)->nullable();
            $table->timestamps();

            $table->unique(['global_image_gallery_id', 'slot']);
            $table->index('slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_image_gallery_items');
    }
};
