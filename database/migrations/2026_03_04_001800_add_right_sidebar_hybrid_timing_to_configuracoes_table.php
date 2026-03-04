<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarHybridVideoDuration')) {
                $table->unsignedInteger('rightSidebarHybridVideoDuration')
                    ->default(120)
                    ->after('rightSidebarImageFit');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarHybridImageDuration')) {
                $table->unsignedInteger('rightSidebarHybridImageDuration')
                    ->default(120)
                    ->after('rightSidebarHybridVideoDuration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarHybridImageDuration')) {
                $table->dropColumn('rightSidebarHybridImageDuration');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarHybridVideoDuration')) {
                $table->dropColumn('rightSidebarHybridVideoDuration');
            }
        });
    }
};
