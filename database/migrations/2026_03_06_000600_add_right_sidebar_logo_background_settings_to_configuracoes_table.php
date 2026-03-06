<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoBackgroundColor')) {
                $table->string('rightSidebarLogoBackgroundColor', 9)
                    ->default('#0f172a')
                    ->after('rightSidebarLogoHeight');
            }

            if (! Schema::hasColumn('configuracoes', 'isRightSidebarLogoBackgroundTransparent')) {
                $table->boolean('isRightSidebarLogoBackgroundTransparent')
                    ->default(false)
                    ->after('rightSidebarLogoBackgroundColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'isRightSidebarLogoBackgroundTransparent')) {
                $table->dropColumn('isRightSidebarLogoBackgroundTransparent');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarLogoBackgroundColor')) {
                $table->dropColumn('rightSidebarLogoBackgroundColor');
            }
        });
    }
};
