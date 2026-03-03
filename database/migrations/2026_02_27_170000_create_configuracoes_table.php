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
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            
            // API Configuration
            $table->string('apiUrl')->default('http://localhost:8000/api/produtos');
            $table->integer('apiRefreshInterval')->default(60);
            
            // Colors
            $table->string('priceColor')->default('#000000');
            $table->string('offerColor')->default('#FF0000');
            $table->string('rowBackgroundColor')->default('#FFFFFFFF');
            $table->string('borderColor')->default('#000000');
            $table->string('appBackgroundColor')->default('#FFFFFF');
            $table->string('mainBorderColor')->default('#000000');
            $table->string('gradientStartColor')->default('#FFFFFF');
            $table->string('gradientEndColor')->default('#FFFFFF');
            
            // Gradient Settings
            $table->boolean('useGradient')->default(false);
            $table->double('gradientStop1')->default(0.0);
            $table->double('gradientStop2')->default(1.0);
            
            // Border Settings
            $table->boolean('showBorder')->default(true);
            $table->boolean('isMainBorderEnabled')->default(false);
            
            // Image Settings
            $table->boolean('showImage')->default(true);
            $table->integer('imageSize')->default(64);
            
            // Pagination Settings
            $table->boolean('isPaginationEnabled')->default(false);
            $table->integer('pageSize')->default(10);
            $table->integer('paginationInterval')->default(5);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
