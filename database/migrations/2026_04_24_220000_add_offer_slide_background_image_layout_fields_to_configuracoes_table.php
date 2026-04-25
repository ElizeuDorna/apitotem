<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundImageFullScreen')) {
                $table->boolean('offerSlideBackgroundImageFullScreen')
                    ->default(true)
                    ->after('offerSlideBackgroundImageUrl');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundImageWidth')) {
                $table->unsignedInteger('offerSlideBackgroundImageWidth')
                    ->default(0)
                    ->after('offerSlideBackgroundImageFullScreen');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundImageHeight')) {
                $table->unsignedInteger('offerSlideBackgroundImageHeight')
                    ->default(0)
                    ->after('offerSlideBackgroundImageWidth');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundImageMarginTop')) {
                $table->unsignedInteger('offerSlideBackgroundImageMarginTop')
                    ->default(0)
                    ->after('offerSlideBackgroundImageHeight');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundImageMarginBottom')) {
                $table->unsignedInteger('offerSlideBackgroundImageMarginBottom')
                    ->default(0)
                    ->after('offerSlideBackgroundImageMarginTop');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            foreach ([
                'offerSlideBackgroundImageMarginBottom',
                'offerSlideBackgroundImageMarginTop',
                'offerSlideBackgroundImageHeight',
                'offerSlideBackgroundImageWidth',
                'offerSlideBackgroundImageFullScreen',
            ] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};