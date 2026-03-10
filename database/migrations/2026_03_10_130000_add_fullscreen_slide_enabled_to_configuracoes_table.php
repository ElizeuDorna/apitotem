<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        if (Schema::hasColumn('configuracoes', 'fullScreenSlideEnabled')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->boolean('fullScreenSlideEnabled')
                ->default(false)
                ->after('fullScreenSlideReturnDelaySeconds');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        if (!Schema::hasColumn('configuracoes', 'fullScreenSlideEnabled')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->dropColumn('fullScreenSlideEnabled');
        });
    }
};
