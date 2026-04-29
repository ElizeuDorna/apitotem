<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (! Schema::hasColumn('galeria_novas', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('code');
                $table->index('empresa_id');
            }
        });

        DB::table('galeria_novas')
            ->whereNull('empresa_id')
            ->whereNotNull('created_by')
            ->orderBy('id')
            ->get(['id', 'created_by'])
            ->each(function ($gallery) {
                $empresaId = DB::table('users')->where('id', $gallery->created_by)->value('empresa_id');

                if ($empresaId) {
                    DB::table('galeria_novas')->where('id', $gallery->id)->update(['empresa_id' => $empresaId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('galeria_novas', function (Blueprint $table) {
            if (Schema::hasColumn('galeria_novas', 'empresa_id')) {
                $table->dropIndex(['empresa_id']);
                $table->dropColumn('empresa_id');
            }
        });
    }
};