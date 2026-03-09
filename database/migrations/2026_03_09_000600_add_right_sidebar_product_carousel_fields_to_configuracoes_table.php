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
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductCarouselEnabled')) {
                $table->boolean('rightSidebarProductCarouselEnabled')->default(false)->after('rightSidebarHybridImageDuration');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductDisplayMode')) {
                $table->string('rightSidebarProductDisplayMode', 30)->default('all')->after('rightSidebarProductCarouselEnabled');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductTransitionMode')) {
                $table->string('rightSidebarProductTransitionMode', 30)->default('products_only')->after('rightSidebarProductDisplayMode');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductInterval')) {
                $table->integer('rightSidebarProductInterval')->default(8)->after('rightSidebarProductTransitionMode');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductShowImage')) {
                $table->boolean('rightSidebarProductShowImage')->default(true)->after('rightSidebarProductInterval');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductShowName')) {
                $table->boolean('rightSidebarProductShowName')->default(true)->after('rightSidebarProductShowImage');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductShowPrice')) {
                $table->boolean('rightSidebarProductShowPrice')->default(true)->after('rightSidebarProductShowName');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductNamePosition')) {
                $table->string('rightSidebarProductNamePosition', 10)->default('top')->after('rightSidebarProductShowPrice');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductPricePosition')) {
                $table->string('rightSidebarProductPricePosition', 10)->default('bottom')->after('rightSidebarProductNamePosition');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductNameColor')) {
                $table->string('rightSidebarProductNameColor', 9)->default('#FFFFFF')->after('rightSidebarProductPricePosition');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductPriceColor')) {
                $table->string('rightSidebarProductPriceColor', 9)->default('#FDE68A')->after('rightSidebarProductNameColor');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductNameBadgeEnabled')) {
                $table->boolean('rightSidebarProductNameBadgeEnabled')->default(true)->after('rightSidebarProductPriceColor');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductNameBadgeColor')) {
                $table->string('rightSidebarProductNameBadgeColor', 9)->default('#0F172A')->after('rightSidebarProductNameBadgeEnabled');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductPriceBadgeEnabled')) {
                $table->boolean('rightSidebarProductPriceBadgeEnabled')->default(true)->after('rightSidebarProductNameBadgeColor');
            }
            if (!Schema::hasColumn('configuracoes', 'rightSidebarProductPriceBadgeColor')) {
                $table->string('rightSidebarProductPriceBadgeColor', 9)->default('#0F172A')->after('rightSidebarProductPriceBadgeEnabled');
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
                'rightSidebarProductCarouselEnabled',
                'rightSidebarProductDisplayMode',
                'rightSidebarProductTransitionMode',
                'rightSidebarProductInterval',
                'rightSidebarProductShowImage',
                'rightSidebarProductShowName',
                'rightSidebarProductShowPrice',
                'rightSidebarProductNamePosition',
                'rightSidebarProductPricePosition',
                'rightSidebarProductNameColor',
                'rightSidebarProductPriceColor',
                'rightSidebarProductNameBadgeEnabled',
                'rightSidebarProductNameBadgeColor',
                'rightSidebarProductPriceBadgeEnabled',
                'rightSidebarProductPriceBadgeColor',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
