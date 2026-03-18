<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('galeria_nova_items')) {
            Schema::drop('galeria_nova_items');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('galeria_nova_items')) {
            Schema::create('galeria_nova_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('galeria_nova_id')->constrained('galeria_novas')->cascadeOnDelete();
                $table->unsignedTinyInteger('slot');
                $table->string('source_type', 10);
                $table->string('external_url', 1000)->nullable();
                $table->string('file_path', 1000)->nullable();
                $table->timestamps();

                $table->unique(['galeria_nova_id', 'slot']);
                $table->index('slot');
            });
        }
    }
};
