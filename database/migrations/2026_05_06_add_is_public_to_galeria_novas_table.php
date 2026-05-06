<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (! Schema::hasColumn('galeria_novas', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('empresa_id');
                $table->index('is_public');
            }
        });
    }

    public function down(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (Schema::hasColumn('galeria_novas', 'is_public')) {
                $table->dropIndex(['is_public']);
                $table->dropColumn('is_public');
            }
        });
    }
};
