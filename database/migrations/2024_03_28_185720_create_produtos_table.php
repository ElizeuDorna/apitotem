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
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo')->unique();
            $table->string('nome');
            $table->string('preco');
            $table->string('precooferta')->default(0);
            $table->integer('seguencia');
            $table->integer('departamentoid')->unsigned()->default(0);
            $table->string('urlimg');
            $table->string('urlsemimg');
            //$table->foreign('departamentoid')->references('id')->on('departmentos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};

