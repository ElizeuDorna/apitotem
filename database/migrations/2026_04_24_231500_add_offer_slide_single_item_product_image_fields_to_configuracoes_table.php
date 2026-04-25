<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageEnabled')) {
                $table->boolean('offerSlideSingleItemProductImageEnabled')
                    ->default(false)
                    ->after('offerSlideBackgroundImageMarginBottom');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageWidth')) {
                $table->unsignedInteger('offerSlideSingleItemProductImageWidth')
                    ->default(320)
                    ->after('offerSlideSingleItemProductImageEnabled');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageHeight')) {
                $table->unsignedInteger('offerSlideSingleItemProductImageHeight')
                    ->default(320)
                    ->after('offerSlideSingleItemProductImageWidth');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageTop')) {
                $table->unsignedInteger('offerSlideSingleItemProductImageTop')
                    ->default(32)
                    ->after('offerSlideSingleItemProductImageHeight');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageRight')) {
                $table->unsignedInteger('offerSlideSingleItemProductImageRight')
                    ->default(32)
                    ->after('offerSlideSingleItemProductImageTop');
            }

        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            foreach ([
                'offerSlideSingleItemProductImageRight',
                'offerSlideSingleItemProductImageTop',
                'offerSlideSingleItemProductImageHeight',
                'offerSlideSingleItemProductImageWidth',
                'offerSlideSingleItemProductImageEnabled',
            ] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};