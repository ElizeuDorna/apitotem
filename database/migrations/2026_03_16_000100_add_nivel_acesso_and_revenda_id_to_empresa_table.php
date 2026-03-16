<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (! Schema::hasColumn('empresa', 'nivel_acesso')) {
                $table->unsignedTinyInteger('nivel_acesso')->default(1)->after('api_token');
            }

            if (! Schema::hasColumn('empresa', 'revenda_id')) {
                $table->unsignedBigInteger('revenda_id')->nullable()->after('nivel_acesso');
                $table->foreign('revenda_id')->references('id')->on('empresa')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (Schema::hasColumn('empresa', 'revenda_id')) {
                $table->dropForeign(['revenda_id']);
                $table->dropColumn('revenda_id');
            }

            if (Schema::hasColumn('empresa', 'nivel_acesso')) {
                $table->dropColumn('nivel_acesso');
            }
        });
    }
};
