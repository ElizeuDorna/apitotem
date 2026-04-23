<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->boolean('offerSlideEnabled')->default(false)->after('groupLabelBadgeColor');
            $table->unsignedInteger('offerSlideIntervalSeconds')->default(300)->after('offerSlideEnabled');
            $table->string('offerSlideDescriptionFontFamily', 30)->default('arial')->after('offerSlideIntervalSeconds');
            $table->unsignedInteger('offerSlideDescriptionFontSize')->default(42)->after('offerSlideDescriptionFontFamily');
            $table->string('offerSlideDescriptionColor', 9)->default('#FFFFFF')->after('offerSlideDescriptionFontSize');
            $table->string('offerSlideDescriptionPosition', 10)->default('top')->after('offerSlideDescriptionColor');
            $table->string('offerSlidePriceFontFamily', 30)->default('arial')->after('offerSlideDescriptionPosition');
            $table->unsignedInteger('offerSlidePriceFontSize')->default(72)->after('offerSlidePriceFontFamily');
            $table->string('offerSlidePriceColor', 9)->default('#FDE68A')->after('offerSlidePriceFontSize');
            $table->string('offerSlidePricePosition', 10)->default('bottom')->after('offerSlidePriceColor');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->dropColumn([
                'offerSlideEnabled',
                'offerSlideIntervalSeconds',
                'offerSlideDescriptionFontFamily',
                'offerSlideDescriptionFontSize',
                'offerSlideDescriptionColor',
                'offerSlideDescriptionPosition',
                'offerSlidePriceFontFamily',
                'offerSlidePriceFontSize',
                'offerSlidePriceColor',
                'offerSlidePricePosition',
            ]);
        });
    }
};