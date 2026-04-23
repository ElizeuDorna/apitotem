<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_configurations') && Schema::hasColumn('device_configurations', 'template_id')) {
            Schema::table('device_configurations', function (Blueprint $table) {
                try {
                    $table->dropForeign(['template_id']);
                } catch (Throwable $exception) {
                    // Ignore if the foreign key was already removed.
                }

                $table->dropColumn('template_id');
            });
        }

        Schema::dropIfExists('template_items');
        Schema::dropIfExists('templates');
    }

    public function down(): void
    {
        if (! Schema::hasTable('templates')) {
            Schema::create('templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
                $table->string('nome', 120);
                $table->string('tipo_layout', 50);
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('template_items')) {
            Schema::create('template_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
                $table->string('tipo', 50);
                $table->unsignedInteger('ordem')->default(1);
                $table->text('conteudo')->nullable();
                $table->json('config_json')->nullable();
                $table->timestamps();

                $table->index(['template_id', 'ordem']);
                $table->index(['template_id', 'tipo']);
            });
        }

        if (Schema::hasTable('templates')) {
            Schema::table('templates', function (Blueprint $table) {
                if (! Schema::hasColumn('templates', 'web_config_payload')) {
                    $table->json('web_config_payload')->nullable()->after('tipo_layout');
                }

                if (! Schema::hasColumn('templates', 'is_default_web')) {
                    $table->boolean('is_default_web')->default(false)->after('web_config_payload');
                }
            });

            Schema::table('templates', function (Blueprint $table) {
                try {
                    $table->index(['empresa_id', 'is_default_web'], 'templates_empresa_default_web_idx');
                } catch (Throwable $exception) {
                    // Ignore if the index already exists.
                }
            });
        }

        if (Schema::hasTable('device_configurations') && ! Schema::hasColumn('device_configurations', 'template_id')) {
            Schema::table('device_configurations', function (Blueprint $table) {
                $table->foreignId('template_id')->nullable()->after('device_id')->constrained('templates')->nullOnDelete();
            });
        }
    }
};