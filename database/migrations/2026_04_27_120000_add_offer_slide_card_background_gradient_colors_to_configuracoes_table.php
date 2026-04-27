<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBackgroundColorStart')) {
                $table->string('offerSlideCardBackgroundColorStart', 9)
                    ->default('#0F172A')
                    ->after('offerSlideCardBackgroundColor');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBackgroundColorEnd')) {
                $table->string('offerSlideCardBackgroundColorEnd', 9)
                    ->default('#0F172A')
                    ->after('offerSlideCardBackgroundColorStart');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            foreach ([
                'offerSlideCardBackgroundColorEnd',
                'offerSlideCardBackgroundColorStart',
            ] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};