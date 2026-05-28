<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_integrations', function (Blueprint $table) {
            if (! Schema::hasColumn('social_media_integrations', 'meta_user_access_token')) {
                $table->text('meta_user_access_token')->nullable()->after('access_token_expires_at');
            }

            if (! Schema::hasColumn('social_media_integrations', 'meta_user_access_token_expires_at')) {
                $table->timestamp('meta_user_access_token_expires_at')->nullable()->after('meta_user_access_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_media_integrations', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('social_media_integrations', 'meta_user_access_token_expires_at')
                    ? 'meta_user_access_token_expires_at'
                    : null,
                Schema::hasColumn('social_media_integrations', 'meta_user_access_token')
                    ? 'meta_user_access_token'
                    : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
