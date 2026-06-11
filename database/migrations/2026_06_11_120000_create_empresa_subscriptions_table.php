<?php

use App\Models\EmpresaSubscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->unique();
            $table->string('status', 30)->default(EmpresaSubscription::STATUS_ACTIVE);
            $table->date('starts_at')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->date('access_expires_at')->nullable();
            $table->date('grace_ends_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->string('blocked_reason', 255)->nullable();
            $table->string('plan_name', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'access_expires_at'], 'es_status_exp_idx');
            $table->foreign('empresa_id', 'es_empresa_fk')->references('id')->on('empresa')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_subscriptions');
    }
};