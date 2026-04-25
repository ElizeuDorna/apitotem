<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlidePriceAlignment')) {
                $table->string('offerSlidePriceAlignment', 10)
                    ->default('left')
                    ->after('offerSlidePricePosition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlidePriceAlignment')) {
                $table->dropColumn('offerSlidePriceAlignment');
            }
        });
    }
};