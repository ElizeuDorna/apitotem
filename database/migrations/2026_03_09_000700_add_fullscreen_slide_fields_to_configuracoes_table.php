<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideImageUrls')) {
                $table->text('fullScreenSlideImageUrls')->nullable()->after('rightSidebarImageUrls');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideInterval')) {
                $table->unsignedInteger('fullScreenSlideInterval')->default(8)->after('fullScreenSlideImageUrls');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'fullScreenSlideInterval')) {
                $table->dropColumn('fullScreenSlideInterval');
            }

            if (Schema::hasColumn('configuracoes', 'fullScreenSlideImageUrls')) {
                $table->dropColumn('fullScreenSlideImageUrls');
            }
        });
    }
};
