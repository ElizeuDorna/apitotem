<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarImageHeight')) {
                $table->unsignedSmallInteger('rightSidebarImageHeight')->default(96)->after('rightSidebarImageFit');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarImageWidth')) {
                $table->unsignedSmallInteger('rightSidebarImageWidth')->default(0)->after('rightSidebarImageHeight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarImageWidth')) {
                $table->dropColumn('rightSidebarImageWidth');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarImageHeight')) {
                $table->dropColumn('rightSidebarImageHeight');
            }
        });
    }
};
