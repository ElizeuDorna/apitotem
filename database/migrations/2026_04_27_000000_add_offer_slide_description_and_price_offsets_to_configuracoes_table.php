<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideDescriptionOffsetY')) {
                $table->integer('offerSlideDescriptionOffsetY')->default(0)->after('offerSlideDescriptionPosition');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideDescriptionOffsetX')) {
                $table->integer('offerSlideDescriptionOffsetX')->default(0)->after('offerSlideDescriptionOffsetY');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlidePriceOffsetY')) {
                $table->integer('offerSlidePriceOffsetY')->default(0)->after('offerSlidePriceAlignment');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlidePriceOffsetX')) {
                $table->integer('offerSlidePriceOffsetX')->default(0)->after('offerSlidePriceOffsetY');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlidePriceOffsetX')) {
                $table->dropColumn('offerSlidePriceOffsetX');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlidePriceOffsetY')) {
                $table->dropColumn('offerSlidePriceOffsetY');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlideDescriptionOffsetX')) {
                $table->dropColumn('offerSlideDescriptionOffsetX');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlideDescriptionOffsetY')) {
                $table->dropColumn('offerSlideDescriptionOffsetY');
            }
        });
    }
};