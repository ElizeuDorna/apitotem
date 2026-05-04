<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'panelSidebarFontFamily')) {
                $table->string('panelSidebarFontFamily', 120)->nullable()->after('panelBrandIconUrl');
            }

            if (! Schema::hasColumn('configuracoes', 'panelSidebarFontSize')) {
                $table->decimal('panelSidebarFontSize', 4, 1)->nullable()->after('panelSidebarFontFamily');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'panelSidebarFontSize')) {
                $table->dropColumn('panelSidebarFontSize');
            }

            if (Schema::hasColumn('configuracoes', 'panelSidebarFontFamily')) {
                $table->dropColumn('panelSidebarFontFamily');
            }
        });
    }
};
