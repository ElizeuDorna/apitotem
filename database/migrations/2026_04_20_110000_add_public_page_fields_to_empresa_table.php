<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->boolean('public_page_enabled')->default(false)->after('urlimagem');
            $table->string('public_page_slug', 120)->nullable()->unique()->after('public_page_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropUnique(['public_page_slug']);
            $table->dropColumn(['public_page_enabled', 'public_page_slug']);
        });
    }
};