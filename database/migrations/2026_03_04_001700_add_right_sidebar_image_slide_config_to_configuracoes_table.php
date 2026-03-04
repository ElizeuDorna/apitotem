<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'rightSidebarMediaType')) {
                $table->string('rightSidebarMediaType', 20)->default('video')->after('rightSidebarBorderWidth');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarImageUrls')) {
                $table->text('rightSidebarImageUrls')->nullable()->after('rightSidebarMediaType');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarImageInterval')) {
                $table->unsignedSmallInteger('rightSidebarImageInterval')->default(8)->after('rightSidebarImageUrls');
            }

            if (! Schema::hasColumn('configuracoes', 'rightSidebarImageFit')) {
                $table->string('rightSidebarImageFit', 20)->default('contain')->after('rightSidebarImageInterval');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (Schema::hasColumn('configuracoes', 'rightSidebarImageFit')) {
                $table->dropColumn('rightSidebarImageFit');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarImageInterval')) {
                $table->dropColumn('rightSidebarImageInterval');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarImageUrls')) {
                $table->dropColumn('rightSidebarImageUrls');
            }

            if (Schema::hasColumn('configuracoes', 'rightSidebarMediaType')) {
                $table->dropColumn('rightSidebarMediaType');
            }
        });
    }
};
