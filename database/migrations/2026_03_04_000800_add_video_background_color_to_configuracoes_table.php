<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'videoBackgroundColor')) {
                $table->string('videoBackgroundColor', 9)->default('#000000')->after('productsPanelBackgroundColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'videoBackgroundColor')) {
                $table->dropColumn('videoBackgroundColor');
            }
        });
    }
};
