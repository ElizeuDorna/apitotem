<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideLayoutMode')) {
                $table->string('offerSlideLayoutMode', 30)
                    ->default('double_list')
                    ->after('offerSlideBackgroundColorEnd');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlideLayoutMode')) {
                $table->dropColumn('offerSlideLayoutMode');
            }
        });
    }
};