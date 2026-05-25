<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->string('provider', 40)->default('instagram_graph');
            $table->string('status', 30)->default('disconnected');
            $table->string('instagram_user_id', 120)->nullable();
            $table->string('instagram_username', 120)->nullable();
            $table->string('instagram_business_account_id', 120)->nullable();
            $table->string('facebook_page_id', 120)->nullable();
            $table->string('facebook_page_name', 160)->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'provider'], 'social_media_integrations_empresa_provider_unique');
            $table->index(['provider', 'status'], 'social_media_integrations_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_integrations');
    }
};