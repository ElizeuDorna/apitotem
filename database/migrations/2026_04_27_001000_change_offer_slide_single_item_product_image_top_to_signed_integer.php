<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageTop INT NOT NULL DEFAULT 32');
    }

    public function down(): void
    {
        DB::table('configuracoes')
            ->where('offerSlideSingleItemProductImageTop', '<', 0)
            ->update(['offerSlideSingleItemProductImageTop' => 0]);

        DB::statement('ALTER TABLE configuracoes MODIFY offerSlideSingleItemProductImageTop INT UNSIGNED NOT NULL DEFAULT 32');
    }
};