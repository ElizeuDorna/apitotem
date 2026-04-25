<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlideOnlyMode')) {
                $table->dropColumn('offerSlideOnlyMode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideOnlyMode')) {
                $table->boolean('offerSlideOnlyMode')
                    ->default(false)
                    ->after('offerSlideEnabled');
            }
        });
    }
};