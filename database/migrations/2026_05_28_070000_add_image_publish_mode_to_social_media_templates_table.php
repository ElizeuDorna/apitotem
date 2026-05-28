<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('social_media_templates', 'image_publish_mode')) {
                $table->string('image_publish_mode', 30)->default('single')->after('cover_image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_media_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('social_media_templates', 'image_publish_mode')) {
                $table->dropColumn('image_publish_mode');
            }
        });
    }
};