<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('social_media_templates', 'source_type')) {
                $table->string('source_type', 30)->default('manual')->after('image_publish_mode');
            }

            if (! Schema::hasColumn('social_media_templates', 'automation_batch_key')) {
                $table->string('automation_batch_key', 160)->nullable()->after('source_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_media_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('social_media_templates', 'automation_batch_key')) {
                $table->dropColumn('automation_batch_key');
            }

            if (Schema::hasColumn('social_media_templates', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
