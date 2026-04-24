<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundTransparent')) {
                $table->boolean('offerSlideBackgroundTransparent')
                    ->default(false)
                    ->after('offerSlideBackgroundColorEnd');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideBackgroundImageUrl')) {
                $table->string('offerSlideBackgroundImageUrl', 1000)
                    ->nullable()
                    ->after('offerSlideBackgroundTransparent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            foreach (['offerSlideBackgroundImageUrl', 'offerSlideBackgroundTransparent'] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};