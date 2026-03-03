<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            // Adicionar novos campos
            $table->string('codigo', 20)->unique()->after('id');
            $table->string('nome')->after('codigo');
            $table->string('endereco')->nullable()->after('razaosocial');
            $table->string('bairro')->nullable()->after('endereco');
            $table->string('numero', 20)->nullable()->after('bairro');
            $table->string('cep', 10)->nullable()->after('numero');
            
            // Renomear fantasia para nome já será feito manualmente se necessário
            // Por segurança vamos manter os dois campos temporariamente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'nome', 'endereco', 'bairro', 'numero', 'cep']);
        });
    }
};
