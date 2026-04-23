<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('device_configurations', 'web_screen_model_id')) {
            Schema::table('device_configurations', function (Blueprint $table) {
                $table->foreignId('web_screen_model_id')
                    ->nullable()
                    ->after('device_id')
                    ->constrained('web_screen_models')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('device_configurations', 'web_screen_model_id')) {
            Schema::table('device_configurations', function (Blueprint $table) {
                $table->dropForeign(['web_screen_model_id']);
                $table->dropColumn('web_screen_model_id');
            });
        }
    }
};