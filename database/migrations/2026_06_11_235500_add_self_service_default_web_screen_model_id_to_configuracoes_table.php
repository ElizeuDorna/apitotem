<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'selfServiceDefaultWebScreenModelId')) {
                $table->unsignedBigInteger('selfServiceDefaultWebScreenModelId')
                    ->nullable()
                    ->after('selfServiceDefaultMenuPermissions');

                $table->foreign('selfServiceDefaultWebScreenModelId', 'cfg_self_service_web_model_fk')
                    ->references('id')
                    ->on('web_screen_models')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'selfServiceDefaultWebScreenModelId')) {
                $table->dropForeign('cfg_self_service_web_model_fk');
                $table->dropColumn('selfServiceDefaultWebScreenModelId');
            }
        });
    }
};