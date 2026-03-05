<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'groupLabelFontFamily')) {
                $table->string('groupLabelFontFamily', 20)->default('arial')->after('groupLabelFontSize');
            }

            if (! Schema::hasColumn('configuracoes', 'showGroupLabelBadge')) {
                $table->boolean('showGroupLabelBadge')->default(false)->after('groupLabelColor');
            }

            if (! Schema::hasColumn('configuracoes', 'groupLabelBadgeColor')) {
                $table->string('groupLabelBadgeColor', 9)->default('#0f172a')->after('showGroupLabelBadge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'groupLabelBadgeColor')) {
                $table->dropColumn('groupLabelBadgeColor');
            }

            if (Schema::hasColumn('configuracoes', 'showGroupLabelBadge')) {
                $table->dropColumn('showGroupLabelBadge');
            }

            if (Schema::hasColumn('configuracoes', 'groupLabelFontFamily')) {
                $table->dropColumn('groupLabelFontFamily');
            }
        });
    }
};
