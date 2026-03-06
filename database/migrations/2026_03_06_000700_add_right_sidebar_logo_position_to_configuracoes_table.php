<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoPosition')) {
                $table->string('rightSidebarLogoPosition', 40)
                    ->default('sidebar_top')
                    ->after('showRightSidebarLogo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarLogoPosition')) {
                $table->dropColumn('rightSidebarLogoPosition');
            }
        });
    }
};
