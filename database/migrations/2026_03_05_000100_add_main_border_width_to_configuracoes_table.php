<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'mainBorderWidth')) {
                $table->unsignedTinyInteger('mainBorderWidth')->default(1)->after('mainBorderColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'mainBorderWidth')) {
                $table->dropColumn('mainBorderWidth');
            }
        });
    }
};
