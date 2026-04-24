<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundColorStart')) {
                $table->string('offerSlideBackgroundColorStart', 9)
                    ->default('#0F172A')
                    ->after('offerSlideDescriptionPosition');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundColorEnd')) {
                $table->string('offerSlideBackgroundColorEnd', 9)
                    ->default('#020617')
                    ->after('offerSlideBackgroundColorStart');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlideBackgroundColorEnd')) {
                $table->dropColumn('offerSlideBackgroundColorEnd');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlideBackgroundColorStart')) {
                $table->dropColumn('offerSlideBackgroundColorStart');
            }
        });
    }
};