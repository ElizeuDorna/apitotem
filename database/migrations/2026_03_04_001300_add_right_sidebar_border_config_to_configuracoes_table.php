<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'showRightSidebarBorder')) {
                $table->boolean('showRightSidebarBorder')->default(true)->after('showVideoPanel');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarBorderColor')) {
                $table->string('rightSidebarBorderColor', 9)->default('#334155')->after('showRightSidebarBorder');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarBorderColor')) {
                $table->dropColumn('rightSidebarBorderColor');
            }

            if (Schema::hasColumn('configuracoes', 'showRightSidebarBorder')) {
                $table->dropColumn('showRightSidebarBorder');
            }
        });
    }
};
