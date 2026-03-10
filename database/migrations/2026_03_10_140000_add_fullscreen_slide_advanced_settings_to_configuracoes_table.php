<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table): void {
            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideStartDate')) {
                $table->date('fullScreenSlideStartDate')->nullable()->after('fullScreenSlideEnabled');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideEndDate')) {
                $table->date('fullScreenSlideEndDate')->nullable()->after('fullScreenSlideStartDate');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideEnabledWindows')) {
                $table->boolean('fullScreenSlideEnabledWindows')->default(true)->after('fullScreenSlideEndDate');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideEnabledAndroid')) {
                $table->boolean('fullScreenSlideEnabledAndroid')->default(true)->after('fullScreenSlideEnabledWindows');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideImageWidthWindows')) {
                $table->unsignedInteger('fullScreenSlideImageWidthWindows')->default(0)->after('fullScreenSlideEnabledAndroid');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideImageHeightWindows')) {
                $table->unsignedInteger('fullScreenSlideImageHeightWindows')->default(0)->after('fullScreenSlideImageWidthWindows');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideImageWidthAndroid')) {
                $table->unsignedInteger('fullScreenSlideImageWidthAndroid')->default(0)->after('fullScreenSlideImageHeightWindows');
            }

            if (!Schema::hasColumn('configuracoes', 'fullScreenSlideImageHeightAndroid')) {
                $table->unsignedInteger('fullScreenSlideImageHeightAndroid')->default(0)->after('fullScreenSlideImageWidthAndroid');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('configuracoes')) {
            return;
        }

        Schema::table('configuracoes', function (Blueprint $table): void {
            $columns = [
                'fullScreenSlideStartDate',
                'fullScreenSlideEndDate',
                'fullScreenSlideEnabledWindows',
                'fullScreenSlideEnabledAndroid',
                'fullScreenSlideImageWidthWindows',
                'fullScreenSlideImageHeightWindows',
                'fullScreenSlideImageWidthAndroid',
                'fullScreenSlideImageHeightAndroid',
            ];

            $toDrop = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('configuracoes', $column)));
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
