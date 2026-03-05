<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'titlePosition')) {
                $table->string('titlePosition', 10)->default('top')->after('isTitleDynamic');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'titlePosition')) {
                $table->dropColumn('titlePosition');
            }
        });
    }
};
