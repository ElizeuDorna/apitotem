<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'groupLabelFontSize')) {
                $table->unsignedSmallInteger('groupLabelFontSize')->default(14)->after('listFontSize');
            }

            if (! Schema::hasColumn('configuracoes', 'groupLabelColor')) {
                $table->string('groupLabelColor', 9)->default('#cbd5e1')->after('groupLabelFontSize');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'groupLabelColor')) {
                $table->dropColumn('groupLabelColor');
            }

            if (Schema::hasColumn('configuracoes', 'groupLabelFontSize')) {
                $table->dropColumn('groupLabelFontSize');
            }
        });
    }
};
