<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (! Schema::hasColumn('galeria_novas', 'source_type')) {
                $table->string('source_type', 10)->nullable()->after('name');
            }

            if (! Schema::hasColumn('galeria_novas', 'external_url')) {
                $table->string('external_url', 1000)->nullable()->after('source_type');
            }

            if (! Schema::hasColumn('galeria_novas', 'file_path')) {
                $table->string('file_path', 1000)->nullable()->after('external_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (Schema::hasColumn('galeria_novas', 'file_path')) {
                $table->dropColumn('file_path');
            }

            if (Schema::hasColumn('galeria_novas', 'external_url')) {
                $table->dropColumn('external_url');
            }

            if (Schema::hasColumn('galeria_novas', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
