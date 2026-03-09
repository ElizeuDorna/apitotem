<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            if (!Schema::hasColumn('configuracoes', 'rightSidebarLogoPositionWindows')) {
                $table->string('rightSidebarLogoPositionWindows', 40)->default('sidebar_top')->after('rightSidebarLogoPosition');
            }

            if (!Schema::hasColumn('configuracoes', 'rightSidebarLogoPositionAndroid')) {
                $table->string('rightSidebarLogoPositionAndroid', 40)->default('sidebar_top')->after('rightSidebarLogoPositionWindows');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarLogoPositionWindows')) {
                $table->dropColumn('rightSidebarLogoPositionWindows');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarLogoPositionAndroid')) {
                $table->dropColumn('rightSidebarLogoPositionAndroid');
            }
        });
    }
};
