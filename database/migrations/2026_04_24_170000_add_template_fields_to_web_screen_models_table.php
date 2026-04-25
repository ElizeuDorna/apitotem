<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_screen_models', function (Blueprint $table) {
            if (! Schema::hasColumn('web_screen_models', 'is_admin_default')) {
                $table->boolean('is_admin_default')
                    ->default(false)
                    ->after('nome');
            }

            if (! Schema::hasColumn('web_screen_models', 'source_model_id')) {
                $table->unsignedBigInteger('source_model_id')
                    ->nullable()
                    ->after('is_admin_default');

                $table->foreign('source_model_id')
                    ->references('id')
                    ->on('web_screen_models')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('web_screen_models', function (Blueprint $table) {
            if (Schema::hasColumn('web_screen_models', 'source_model_id')) {
                $table->dropForeign(['source_model_id']);
                $table->dropColumn('source_model_id');
            }

            if (Schema::hasColumn('web_screen_models', 'is_admin_default')) {
                $table->dropColumn('is_admin_default');
            }
        });
    }
};