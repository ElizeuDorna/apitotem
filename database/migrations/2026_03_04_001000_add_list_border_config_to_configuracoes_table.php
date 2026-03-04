<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'listBorderColor')) {
                $table->string('listBorderColor', 9)->default('#334155')->after('productsPanelBackgroundColor');
            }

            if (! Schema::hasColumn('configuracoes', 'isListBorderTransparent')) {
                $table->boolean('isListBorderTransparent')->default(false)->after('isProductsPanelTransparent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'isListBorderTransparent')) {
                $table->dropColumn('isListBorderTransparent');
            }

            if (Schema::hasColumn('configuracoes', 'listBorderColor')) {
                $table->dropColumn('listBorderColor');
            }
        });
    }
};
