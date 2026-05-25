<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('social_media_templates', 'facebook_auto_publish')) {
                $table->boolean('facebook_auto_publish')->default(false)->after('instagram_auto_publish');
            }

            if (! Schema::hasColumn('social_media_templates', 'publish_to_instagram')) {
                $table->boolean('publish_to_instagram')->default(true)->after('facebook_auto_publish');
            }

            if (! Schema::hasColumn('social_media_templates', 'publish_to_facebook')) {
                $table->boolean('publish_to_facebook')->default(false)->after('publish_to_instagram');
            }

            if (! Schema::hasColumn('social_media_templates', 'facebook_publish_status')) {
                $table->string('facebook_publish_status', 30)->default('draft')->after('instagram_publish_id');
            }

            if (! Schema::hasColumn('social_media_templates', 'facebook_last_published_at')) {
                $table->timestamp('facebook_last_published_at')->nullable()->after('facebook_publish_status');
            }

            if (! Schema::hasColumn('social_media_templates', 'facebook_last_error')) {
                $table->text('facebook_last_error')->nullable()->after('facebook_last_published_at');
            }

            if (! Schema::hasColumn('social_media_templates', 'facebook_publish_id')) {
                $table->string('facebook_publish_id', 120)->nullable()->after('facebook_last_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_media_templates', function (Blueprint $table): void {
            foreach ([
                'facebook_publish_id',
                'facebook_last_error',
                'facebook_last_published_at',
                'facebook_publish_status',
                'publish_to_facebook',
                'publish_to_instagram',
                'facebook_auto_publish',
            ] as $column) {
                if (Schema::hasColumn('social_media_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};