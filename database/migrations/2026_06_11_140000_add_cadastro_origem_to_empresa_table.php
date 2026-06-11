<?php

use App\Models\Empresa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (! Schema::hasColumn('empresa', 'cadastro_origem')) {
                $table->string('cadastro_origem', 30)
                    ->default(Empresa::CADASTRO_ORIGEM_LEGACY)
                    ->after('revenda_id');

                $table->index('cadastro_origem', 'empresa_cadastro_origem_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            if (Schema::hasColumn('empresa', 'cadastro_origem')) {
                $table->dropIndex('empresa_cadastro_origem_idx');
                $table->dropColumn('cadastro_origem');
            }
        });
    }
};