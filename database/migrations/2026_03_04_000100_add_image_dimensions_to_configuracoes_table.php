<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'imageWidth')) {
                $table->unsignedSmallInteger('imageWidth')->default(56)->after('imageSize');
            }

            if (! Schema::hasColumn('configuracoes', 'imageHeight')) {
                $table->unsignedSmallInteger('imageHeight')->default(56)->after('imageWidth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'imageHeight')) {
                $table->dropColumn('imageHeight');
            }

            if (Schema::hasColumn('configuracoes', 'imageWidth')) {
                $table->dropColumn('imageWidth');
            }
        });
    }
};
