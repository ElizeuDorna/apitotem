<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageBorderColor')) {
                $table->string('offerSlideSingleItemProductImageBorderColor', 9)
                    ->default('#ffffff1a')
                    ->after('offerSlideSingleItemProductImageVerticalPosition');
            }

            if (! Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageBorderWidth')) {
                $table->unsignedInteger('offerSlideSingleItemProductImageBorderWidth')
                    ->default(1)
                    ->after('offerSlideSingleItemProductImageBorderColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageBorderWidth')) {
                $table->dropColumn('offerSlideSingleItemProductImageBorderWidth');
            }

            if (Schema::hasColumn('configuracoes', 'offerSlideSingleItemProductImageBorderColor')) {
                $table->dropColumn('offerSlideSingleItemProductImageBorderColor');
            }
        });
    }
};