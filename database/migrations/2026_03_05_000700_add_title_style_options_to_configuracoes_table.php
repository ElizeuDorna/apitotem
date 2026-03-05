<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('configuracoes', 'titleFontSize')) {
                $table->unsignedTinyInteger('titleFontSize')->default(32)->after('titlePosition');
            }

            if (! Schema::hasColumn('configuracoes', 'titleFontFamily')) {
                $table->string('titleFontFamily', 20)->default('arial')->after('titleFontSize');
            }

            if (! Schema::hasColumn('configuracoes', 'titleBackgroundColor')) {
                $table->string('titleBackgroundColor', 9)->default('#0f172a')->after('titleFontFamily');
            }

            if (! Schema::hasColumn('configuracoes', 'isTitleBackgroundTransparent')) {
                $table->boolean('isTitleBackgroundTransparent')->default(false)->after('titleBackgroundColor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            if (Schema::hasColumn('configuracoes', 'isTitleBackgroundTransparent')) {
                $table->dropColumn('isTitleBackgroundTransparent');
            }

            if (Schema::hasColumn('configuracoes', 'titleBackgroundColor')) {
                $table->dropColumn('titleBackgroundColor');
            }

            if (Schema::hasColumn('configuracoes', 'titleFontFamily')) {
                $table->dropColumn('titleFontFamily');
            }

            if (Schema::hasColumn('configuracoes', 'titleFontSize')) {
                $table->dropColumn('titleFontSize');
            }
        });
    }
};
