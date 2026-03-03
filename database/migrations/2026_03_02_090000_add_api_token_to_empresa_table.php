<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (! Schema::hasColumn('empresa', 'api_token')) {
                $table->string('api_token', 80)->unique()->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (Schema::hasColumn('empresa', 'api_token')) {
                $table->dropUnique('empresa_api_token_unique');
                $table->dropColumn('api_token');
            }
        });
    }
};
