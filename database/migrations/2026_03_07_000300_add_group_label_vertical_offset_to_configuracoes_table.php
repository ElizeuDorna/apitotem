<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            if (!Schema::hasColumn('configuracoes', 'groupLabelVerticalOffset')) {
                $table->integer('groupLabelVerticalOffset')->default(0)->after('groupLabelFontSize');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'groupLabelVerticalOffset')) {
                $table->dropColumn('groupLabelVerticalOffset');
            }
        });
    }
};
