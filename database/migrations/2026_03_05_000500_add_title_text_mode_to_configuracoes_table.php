<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'titleText')) {
                $table->string('titleText', 120)->default('Lista de Produtos (TV)')->after('showTitle');
            }

            if (! Schema::hasColumn('configuracoes', 'isTitleDynamic')) {
                $table->boolean('isTitleDynamic')->default(false)->after('titleText');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'isTitleDynamic')) {
                $table->dropColumn('isTitleDynamic');
            }

            if (Schema::hasColumn('configuracoes', 'titleText')) {
                $table->dropColumn('titleText');
            }
        });
    }
};
