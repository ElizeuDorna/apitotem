<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('configuracoes', 'fullScreenVideoUrl')) {
                $table->text('fullScreenVideoUrl')->nullable()->after('videoUrl');
            }

            if (! Schema::hasColumn('configuracoes', 'fullScreenVideoMuted')) {
                $table->boolean('fullScreenVideoMuted')->default(false)->after('fullScreenVideoUrl');
            }

            if (! Schema::hasColumn('configuracoes', 'fullScreenVideoPlaylist')) {
                $table->json('fullScreenVideoPlaylist')->nullable()->after('fullScreenVideoMuted');
            }

            if (! Schema::hasColumn('configuracoes', 'showFullScreenVideoPanel')) {
                $table->boolean('showFullScreenVideoPanel')->default(false)->after('showVideoPanel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $columns = [
                'fullScreenVideoPlaylist',
                'fullScreenVideoMuted',
                'fullScreenVideoUrl',
                'showFullScreenVideoPanel',
            ];

            $existingColumns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('configuracoes', $column)));

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};