<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageSide')) {
                $table->string('offerSlideSingleItemProductImageSide', 10)
                    ->default('right')
                    ->after('offerSlideSingleItemProductImageRight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageSide')) {
                $table->dropColumn('offerSlideSingleItemProductImageSide');
            }
        });
    }
};