<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarBorderWidth')) {
                $table->unsignedTinyInteger('rightSidebarBorderWidth')->default(1)->after('rightSidebarBorderColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarBorderWidth')) {
                $table->dropColumn('rightSidebarBorderWidth');
            }
        });
    }
};
