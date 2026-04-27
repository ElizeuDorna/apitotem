<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_configurations')) {
            return;
        }

        Schema::table('device_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('device_configurations', 'product_department_id')) {
                $table->foreignId('product_department_id')
                    ->nullable()
                    ->after('web_screen_model_id')
                    ->constrained('departamentos')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('device_configurations', 'product_group_id')) {
                $table->foreignId('product_group_id')
                    ->nullable()
                    ->after('product_department_id')
                    ->constrained('grupos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('device_configurations')) {
            return;
        }

        Schema::table('device_configurations', function (Blueprint $table) {
            foreach (['product_group_id', 'product_department_id'] as $column) {
                if (Schema::hasColumn('device_configurations', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};