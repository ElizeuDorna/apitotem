<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('grupo');
        Schema::dropIfExists('departamento');
    }

    public function down(): void
    {
        // Legacy tables intentionally not recreated.
    }
};