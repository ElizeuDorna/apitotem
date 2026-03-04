<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'productsPanelBackgroundColor')) {
                $table->string('productsPanelBackgroundColor', 9)->default('#0f172a')->after('appBackgroundColor');
            }

            if (! Schema::hasColumn('configuracoes', 'isProductsPanelTransparent')) {
                $table->boolean('isProductsPanelTransparent')->default(false)->after('showBackgroundImage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'isProductsPanelTransparent')) {
                $table->dropColumn('isProductsPanelTransparent');
            }

            if (Schema::hasColumn('configuracoes', 'productsPanelBackgroundColor')) {
                $table->dropColumn('productsPanelBackgroundColor');
            }
        });
    }
};
