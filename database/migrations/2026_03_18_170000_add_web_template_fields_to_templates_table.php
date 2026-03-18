<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->json('web_config_payload')->nullable()->after('tipo_layout');
            $table->boolean('is_default_web')->default(false)->after('web_config_payload');

            $table->index(['empresa_id', 'is_default_web'], 'templates_empresa_default_web_idx');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropIndex('templates_empresa_default_web_idx');
            $table->dropColumn(['web_config_payload', 'is_default_web']);
        });
    }
};
