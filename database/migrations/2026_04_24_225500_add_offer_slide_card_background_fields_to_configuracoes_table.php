<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBackgroundColor')) {
                $table->string('offerSlideCardBackgroundColor', 9)
                    ->default('#0F172A')
                    ->after('offerSlideSmoothTransitionEnabled');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBackgroundTransparent')) {
                $table->boolean('offerSlideCardBackgroundTransparent')
                    ->default(false)
                    ->after('offerSlideCardBackgroundColor');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBackgroundTransparencyPercent')) {
                $table->unsignedTinyInteger('offerSlideCardBackgroundTransparencyPercent')
                    ->default(0)
                    ->after('offerSlideCardBackgroundTransparent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            foreach ([
                'offerSlideCardBackgroundTransparencyPercent',
                'offerSlideCardBackgroundTransparent',
                'offerSlideCardBackgroundColor',
            ] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};