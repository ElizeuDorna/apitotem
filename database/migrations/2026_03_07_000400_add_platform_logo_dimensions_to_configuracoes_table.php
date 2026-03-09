<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            if (!Schema::hasColumn('configuracoes', 'rightSidebarLogoWidthWindows')) {
                $table->integer('rightSidebarLogoWidthWindows')->default(220)->after('rightSidebarLogoWidth');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarLogoHeightWindows')) {
                $table->integer('rightSidebarLogoHeightWindows')->default(58)->after('rightSidebarLogoHeight');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarLogoWidthAndroid')) {
                $table->integer('rightSidebarLogoWidthAndroid')->default(220)->after('rightSidebarLogoHeightWindows');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarLogoHeightAndroid')) {
                $table->integer('rightSidebarLogoHeightAndroid')->default(58)->after('rightSidebarLogoWidthAndroid');
            }

            if (!Schema::hasColumn('configuracoes', 'leftVerticalLogoWidthWindows')) {
                $table->integer('leftVerticalLogoWidthWindows')->default(120)->after('leftVerticalLogoWidth');
            }
            if (!Schema::hasColumn('configuracoes', 'leftVerticalLogoHeightWindows')) {
                $table->integer('leftVerticalLogoHeightWindows')->default(220)->after('leftVerticalLogoHeight');
            }
            if (!Schema::hasColumn('configuracoes', 'leftVerticalLogoWidthAndroid')) {
                $table->integer('leftVerticalLogoWidthAndroid')->default(120)->after('leftVerticalLogoHeightWindows');
            }
            if (!Schema::hasColumn('configuracoes', 'leftVerticalLogoHeightAndroid')) {
                $table->integer('leftVerticalLogoHeightAndroid')->default(220)->after('leftVerticalLogoWidthAndroid');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            $columns = [
                'rightSidebarLogoWidthWindows',
                'rightSidebarLogoHeightWindows',
                'rightSidebarLogoWidthAndroid',
                'rightSidebarLogoHeightAndroid',
                'leftVerticalLogoWidthWindows',
                'leftVerticalLogoHeightWindows',
                'leftVerticalLogoWidthAndroid',
                'leftVerticalLogoHeightAndroid',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
