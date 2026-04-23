<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarProductImageWidth')) {
                $table->unsignedInteger('rightSidebarProductImageWidth')->default(0)->after('rightSidebarProductShowImage');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarProductImageHeight')) {
                $table->unsignedInteger('rightSidebarProductImageHeight')->default(0)->after('rightSidebarProductImageWidth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarProductImageHeight')) {
                $table->dropColumn('rightSidebarProductImageHeight');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarProductImageWidth')) {
                $table->dropColumn('rightSidebarProductImageWidth');
            }
        });
    }
};