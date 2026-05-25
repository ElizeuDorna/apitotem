<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'metaAppId')) {
                $table->string('metaAppId', 120)->nullable()->after('produtoFormImagePreviewSize');
            }

            if (! Schema::hasColumn('configuracoes', 'metaRedirectUri')) {
                $table->string('metaRedirectUri', 500)->nullable()->after('metaAppId');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'metaRedirectUri')) {
                $table->dropColumn('metaRedirectUri');
            }

            if (Schema::hasColumn('configuracoes', 'metaAppId')) {
                $table->dropColumn('metaAppId');
            }
        });
    }
};