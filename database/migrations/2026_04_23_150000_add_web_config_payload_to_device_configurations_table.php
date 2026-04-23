<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_configurations', function (Blueprint $table) {
            $table->json('web_config_payload')->nullable()->after('template_id');
        });
    }

    public function down(): void
    {
        Schema::table('device_configurations', function (Blueprint $table) {
            $table->dropColumn('web_config_payload');
        });
    }
};