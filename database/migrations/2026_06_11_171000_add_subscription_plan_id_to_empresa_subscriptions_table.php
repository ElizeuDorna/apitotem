<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('empresa_subscriptions', 'subscription_plan_id')) {
                $table->unsignedBigInteger('subscription_plan_id')->nullable()->after('empresa_id');
                $table->foreign('subscription_plan_id', 'es_plan_fk')->references('id')->on('empresa_subscription_plans')->nullOnDelete();
                $table->index('subscription_plan_id', 'es_plan_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_subscriptions', 'subscription_plan_id')) {
                $table->dropForeign('es_plan_fk');
                $table->dropIndex('es_plan_idx');
                $table->dropColumn('subscription_plan_id');
            }
        });
    }
};