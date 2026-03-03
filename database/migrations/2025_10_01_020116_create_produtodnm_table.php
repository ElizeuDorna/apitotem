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
 // TUDO deve estar dentro desta função de callback, que fornece a variável $table.
    Schema::create('produtodnm', function (Blueprint $table) { 
        $table->id(); 
        // CHAVE ESTRANGEIRA 1
        $table->foreignId('empresa_id') ->constrained('empresa')->onDelete('cascade'); 
        // CHAVE ESTRANGEIRA 2
        $table->foreignId('produto_id')->constrained('produto')->onDelete('cascade'); 
        $table->integer('procod');
        $table->decimal('preco',10,2);
        $table->decimal('precooferta',10,2);
        $table->string('area');
        $table->string('cordescricao')->nullable();
        $table->string('corpreco')->nullable();
        $table->string('corlinha')->nullable();
        $table->char('destaque')->nullable();
        $table->char('lista')->nullable();
        $table->integer('sequencia');
        $table->string('urlimagem1')->nullable();
        $table->string('urlimagem2')->nullable();
        $table->integer('ativo')->nullable();
        $table->timestamps();
        $table->unique(['empresa_id', 'procod']);
        // 4. CHAVE ÚNICA COMPOSTA
        $table->unique(['produto_id', 'empresa_id']);
     
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtodnm');
    }
};
