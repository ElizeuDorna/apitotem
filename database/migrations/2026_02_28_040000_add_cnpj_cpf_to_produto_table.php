<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produto', function (Blueprint $table) {
            $table->string('cnpj_cpf', 14)->nullable()->after('NOME');
        });
    }

    public function down(): void
    {
        Schema::table('produto', function (Blueprint $table) {
            $table->dropColumn('cnpj_cpf');
        });
    }
};
