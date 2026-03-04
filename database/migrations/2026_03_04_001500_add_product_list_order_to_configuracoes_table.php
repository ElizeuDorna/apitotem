<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'productListOrderMode')) {
                $table->string('productListOrderMode', 20)->default('grupo')->after('rightSidebarBorderWidth');
            }

            if (! Schema::hasColumn('configuracoes', 'productDepartmentOrder')) {
                $table->json('productDepartmentOrder')->nullable()->after('productListOrderMode');
            }

            if (! Schema::hasColumn('configuracoes', 'productGroupOrder')) {
                $table->json('productGroupOrder')->nullable()->after('productDepartmentOrder');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'productGroupOrder')) {
                $table->dropColumn('productGroupOrder');
            }

            if (Schema::hasColumn('configuracoes', 'productDepartmentOrder')) {
                $table->dropColumn('productDepartmentOrder');
            }

            if (Schema::hasColumn('configuracoes', 'productListOrderMode')) {
                $table->dropColumn('productListOrderMode');
            }
        });
    }
};
