<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'showLeftVerticalLogo')) {
                $table->boolean('showLeftVerticalLogo')->default(false)->after('showRightSidebarLogo');
            }

            if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoUrl')) {
                $table->string('leftVerticalLogoUrl', 1000)->nullable()->after('rightSidebarLogoUrl');
            }

            if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoWidth')) {
                $table->unsignedInteger('leftVerticalLogoWidth')->default(120)->after('leftVerticalLogoUrl');
            }

            if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoHeight')) {
                $table->unsignedInteger('leftVerticalLogoHeight')->default(220)->after('leftVerticalLogoWidth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'leftVerticalLogoHeight')) {
                $table->dropColumn('leftVerticalLogoHeight');
            }

            if (Schema::hasColumn('configuracoes', 'leftVerticalLogoWidth')) {
                $table->dropColumn('leftVerticalLogoWidth');
            }

            if (Schema::hasColumn('configuracoes', 'leftVerticalLogoUrl')) {
                $table->dropColumn('leftVerticalLogoUrl');
            }

            if (Schema::hasColumn('configuracoes', 'showLeftVerticalLogo')) {
                $table->dropColumn('showLeftVerticalLogo');
            }
        });
    }
};
