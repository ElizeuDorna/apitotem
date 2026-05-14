<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('produto') || ! Schema::hasColumn('produto', 'CODIGO')) {
            return;
        }

        $hasCodigoTooLong = DB::table('produto')
            ->whereRaw('CHAR_LENGTH(CODIGO) > 14')
            ->exists();

        if ($hasCodigoTooLong) {
            throw new RuntimeException('Existem produtos com CODIGO maior que 14 caracteres. Corrija esses registros antes de executar esta migration.');
        }

        DB::statement('ALTER TABLE produto MODIFY CODIGO VARCHAR(14) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('produto') || ! Schema::hasColumn('produto', 'CODIGO')) {
            return;
        }

        DB::statement('ALTER TABLE produto MODIFY CODIGO VARCHAR(50) NOT NULL');
    }
};