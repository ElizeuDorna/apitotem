<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBorderEnabled')) {
                $table->boolean('offerSlideCardBorderEnabled')
                    ->default(true)
                    ->after('offerSlideLayoutMode');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBorderColor')) {
                $table->string('offerSlideCardBorderColor', 9)
                    ->default('#94a3b8')
                    ->after('offerSlideCardBorderEnabled');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideCardBorderWidth')) {
                $table->unsignedInteger('offerSlideCardBorderWidth')
                    ->default(1)
                    ->after('offerSlideCardBorderColor');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideScreenBorderEnabled')) {
                $table->boolean('offerSlideScreenBorderEnabled')
                    ->default(true)
                    ->after('offerSlideCardBorderWidth');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideScreenBorderColor')) {
                $table->string('offerSlideScreenBorderColor', 9)
                    ->default('#94a3b8')
                    ->after('offerSlideScreenBorderEnabled');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideScreenBorderWidth')) {
                $table->unsignedInteger('offerSlideScreenBorderWidth')
                    ->default(1)
                    ->after('offerSlideScreenBorderColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            foreach ([
                'offerSlideScreenBorderWidth',
                'offerSlideScreenBorderColor',
                'offerSlideScreenBorderEnabled',
                'offerSlideCardBorderWidth',
                'offerSlideCardBorderColor',
                'offerSlideCardBorderEnabled',
            ] as $column) {
                if (Schema::hasColumn('configuracoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};