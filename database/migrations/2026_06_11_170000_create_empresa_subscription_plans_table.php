<?php

use App\Models\EmpresaFinanceiroConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('intervalo_cobranca_dias')->default(EmpresaFinanceiroConfig::INTERVALO_30_DIAS);
            $table->decimal('valor_unitario', 12, 2)->default(0);
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_self_service')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_self_service', 'is_active', 'sort_order'], 'esp_self_active_sort_idx');
        });

        $now = now();

        DB::table('empresa_subscription_plans')->insert([
            [
                'code' => 'mensal',
                'name' => 'Plano Mensal',
                'description' => 'Cobranca mensal automatica.',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
                'valor_unitario' => 29.90,
                'trial_days' => 7,
                'is_active' => true,
                'is_self_service' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'trimestral',
                'name' => 'Plano Trimestral',
                'description' => 'Cobranca trimestral automatica.',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
                'valor_unitario' => 27.90,
                'trial_days' => 7,
                'is_active' => true,
                'is_self_service' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'semestral',
                'name' => 'Plano Semestral',
                'description' => 'Cobranca semestral automatica.',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_180_DIAS,
                'valor_unitario' => 24.90,
                'trial_days' => 7,
                'is_active' => true,
                'is_self_service' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'anual',
                'name' => 'Plano Anual',
                'description' => 'Cobranca anual automatica.',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_1_ANO,
                'valor_unitario' => 19.90,
                'trial_days' => 7,
                'is_active' => true,
                'is_self_service' => true,
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_subscription_plans');
    }
};