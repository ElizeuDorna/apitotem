<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (! Schema::hasColumn('galeria_novas', 'image_hash')) {
                $table->string('image_hash', 64)->nullable()->after('file_path');
                $table->index('image_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (Schema::hasColumn('galeria_novas', 'image_hash')) {
                $table->dropIndex(['image_hash']);
                $table->dropColumn('image_hash');
            }
        });
    }
};
