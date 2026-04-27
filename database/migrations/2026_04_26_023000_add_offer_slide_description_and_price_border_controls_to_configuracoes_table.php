<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideDescriptionBorderEnabled')) {
                $table->boolean('offerSlideDescriptionBorderEnabled')->default(true)->after('offerSlideDescriptionPosition');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideDescriptionBorderColor')) {
                $table->string('offerSlideDescriptionBorderColor', 9)->default('#94a3b8')->after('offerSlideDescriptionBorderEnabled');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideDescriptionBorderWidth')) {
                $table->unsignedInteger('offerSlideDescriptionBorderWidth')->default(1)->after('offerSlideDescriptionBorderColor');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlidePriceBorderEnabled')) {
                $table->boolean('offerSlidePriceBorderEnabled')->default(true)->after('offerSlidePriceAlignment');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlidePriceBorderColor')) {
                $table->string('offerSlidePriceBorderColor', 9)->default('#94a3b8')->after('offerSlidePriceBorderEnabled');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlidePriceBorderWidth')) {
                $table->unsignedInteger('offerSlidePriceBorderWidth')->default(1)->after('offerSlidePriceBorderColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlidePriceBorderWidth')) {
                $table->dropColumn('offerSlidePriceBorderWidth');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlidePriceBorderColor')) {
                $table->dropColumn('offerSlidePriceBorderColor');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlidePriceBorderEnabled')) {
                $table->dropColumn('offerSlidePriceBorderEnabled');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlideDescriptionBorderWidth')) {
                $table->dropColumn('offerSlideDescriptionBorderWidth');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlideDescriptionBorderColor')) {
                $table->dropColumn('offerSlideDescriptionBorderColor');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlideDescriptionBorderEnabled')) {
                $table->dropColumn('offerSlideDescriptionBorderEnabled');
            }
        });
    }
};