<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageRight INT NOT NULL DEFAULT 32');
    }

    public function down(): void
    {
        DB::table('configuracoes')
            ->where('offerSlideSingleItemProductImageRight', '<', 0)
            ->update(['offerSlideSingleItemProductImageRight' => 0]);

        DB::statement('ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageRight INT UNSIGNED NOT NULL DEFAULT 32');
    }
};