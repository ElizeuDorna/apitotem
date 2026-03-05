<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'isRoundedCornersEnabled')) {
                $table->boolean('isRoundedCornersEnabled')->default(true)->after('isMainBorderEnabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'isRoundedCornersEnabled')) {
                $table->dropColumn('isRoundedCornersEnabled');
            }
        });
    }
};
