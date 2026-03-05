<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'productListLeftGroupIds')) {
                $table->json('productListLeftGroupIds')->nullable()->after('productListType');
            }

            if (! Schema::hasColumn('configuracoes', 'productListRightGroupIds')) {
                $table->json('productListRightGroupIds')->nullable()->after('productListLeftGroupIds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'productListRightGroupIds')) {
                $table->dropColumn('productListRightGroupIds');
            }

            if (Schema::hasColumn('configuracoes', 'productListLeftGroupIds')) {
                $table->dropColumn('productListLeftGroupIds');
            }
        });
    }
};
