<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoWidth')) {
                $table->unsignedInteger('rightSidebarLogoWidth')->default(220)->after('rightSidebarLogoUrl');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoHeight')) {
                $table->unsignedInteger('rightSidebarLogoHeight')->default(58)->after('rightSidebarLogoWidth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarLogoHeight')) {
                $table->dropColumn('rightSidebarLogoHeight');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarLogoWidth')) {
                $table->dropColumn('rightSidebarLogoWidth');
            }
        });
    }
};
