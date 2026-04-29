<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->string('senha_integracao_api')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn('senha_integracao_api');
        });
    }
};