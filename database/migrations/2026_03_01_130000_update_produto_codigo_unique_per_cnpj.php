<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM produto'))->pluck('Key_name')->unique();

        if ($indexes->contains('produto_codigo_unique')) {
            DB::statement('ALTER TABLE produto DROP INDEX produto_codigo_unique');
        }

        $indexes = collect(DB::select('SHOW INDEX FROM produto'))->pluck('Key_name')->unique();

        if (! $indexes->contains('produto_codigo_cnpj_cpf_unique')) {
            DB::statement('ALTER TABLE produto ADD UNIQUE INDEX produto_codigo_cnpj_cpf_unique (CODIGO, cnpj_cpf)');
        }
    }

    public function down(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM produto'))->pluck('Key_name')->unique();

        if ($indexes->contains('produto_codigo_cnpj_cpf_unique')) {
            DB::statement('ALTER TABLE produto DROP INDEX produto_codigo_cnpj_cpf_unique');
        }

        $indexes = collect(DB::select('SHOW INDEX FROM produto'))->pluck('Key_name')->unique();

        if (! $indexes->contains('produto_codigo_unique')) {
            DB::statement('ALTER TABLE produto ADD UNIQUE INDEX produto_codigo_unique (CODIGO)');
        }
    }
};
