<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarAndroidHeight')) {
                $table->unsignedSmallInteger('rightSidebarAndroidHeight')->default(0)->after('rightSidebarImageWidth');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarAndroidWidth')) {
                $table->unsignedSmallInteger('rightSidebarAndroidWidth')->default(0)->after('rightSidebarAndroidHeight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarAndroidWidth')) {
                $table->dropColumn('rightSidebarAndroidWidth');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarAndroidHeight')) {
                $table->dropColumn('rightSidebarAndroidHeight');
            }
        });
    }
};
