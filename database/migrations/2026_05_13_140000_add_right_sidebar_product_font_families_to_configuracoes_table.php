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
            if (! Schema::hasColumn('configuracoes', 'rightSidebarProductNameFontFamily')) {
                $table->string('rightSidebarProductNameFontFamily', 40)->default('arial')->after('rightSidebarProductPriceFontSize');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarProductPriceFontFamily')) {
                $table->string('rightSidebarProductPriceFontFamily', 40)->default('arial')->after('rightSidebarProductNameFontFamily');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            foreach (['rightSidebarProductNameFontFamily', 'rightSidebarProductPriceFontFamily'] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};