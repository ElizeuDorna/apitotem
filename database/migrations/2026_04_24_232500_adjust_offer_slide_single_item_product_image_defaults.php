<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageEnabled TINYINT(1) NOT NULL DEFAULT 1");
        DB::statement("ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageRight INT UNSIGNED NOT NULL DEFAULT 3");

        DB::table('configuracoes')
            ->where('offerSlideSingleItemProductImageWidth', 320)
            ->where('offerSlideSingleItemProductImageHeight', 320)
            ->where('offerSlideSingleItemProductImageTop', 32)
            ->where('offerSlideSingleItemProductImageRight', 32)
            ->update([
                'offerSlideSingleItemProductImageEnabled' => 1,
                'offerSlideSingleItemProductImageRight' => 3,
            ]);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageEnabled TINYINT(1) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageRight INT UNSIGNED NOT NULL DEFAULT 32");

        DB::table('configuracoes')
            ->where('offerSlideSingleItemProductImageWidth', 320)
            ->where('offerSlideSingleItemProductImageHeight', 320)
            ->where('offerSlideSingleItemProductImageTop', 32)
            ->where('offerSlideSingleItemProductImageRight', 3)
            ->update([
                'offerSlideSingleItemProductImageEnabled' => 0,
                'offerSlideSingleItemProductImageRight' => 32,
            ]);
    }
};