<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarProductNameFontSize')) {
                $table->unsignedSmallInteger('rightSidebarProductNameFontSize')->default(16)->after('rightSidebarProductPricePosition');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarProductPriceFontSize')) {
                $table->unsignedSmallInteger('rightSidebarProductPriceFontSize')->default(16)->after('rightSidebarProductNameFontSize');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            foreach (['rightSidebarProductNameFontSize', 'rightSidebarProductPriceFontSize'] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};