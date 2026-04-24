<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideTitleText')) {
                $table->string('offerSlideTitleText', 120)
                    ->default('Slide de oferta')
                    ->after('offerSlideBackgroundColorEnd');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideTitleColor')) {
                $table->string('offerSlideTitleColor', 9)
                    ->default('#FDE68A')
                    ->after('offerSlideTitleText');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideTitleFontFamily')) {
                $table->string('offerSlideTitleFontFamily', 40)
                    ->default('arial')
                    ->after('offerSlideTitleColor');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideTitleAlignment')) {
                $table->string('offerSlideTitleAlignment', 20)
                    ->default('left')
                    ->after('offerSlideTitleFontFamily');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            foreach ([
                'offerSlideTitleAlignment',
                'offerSlideTitleFontFamily',
                'offerSlideTitleColor',
                'offerSlideTitleText',
            ] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};