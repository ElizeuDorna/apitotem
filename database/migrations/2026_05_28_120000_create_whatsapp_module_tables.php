<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->string('status', 30)->default('disconnected');
            $table->string('meta_business_account_id', 120)->nullable();
            $table->string('meta_phone_number_id', 120)->nullable();
            $table->string('display_phone_number', 60)->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('empresa_id');
            $table->unique('meta_phone_number_id');
            $table->index(['status', 'meta_phone_number_id']);
        });

        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('whatsapp_number', 40);
            $table->string('whatsapp_number_e164', 25);
            $table->boolean('opt_in_whatsapp')->default(false);
            $table->timestamp('opt_in_whatsapp_at')->nullable();
            $table->string('opt_in_source', 80)->nullable();
            $table->timestamp('last_whatsapp_inbound_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'opt_in_whatsapp']);
            $table->unique(['empresa_id', 'whatsapp_number_e164'], 'whatsapp_contacts_empresa_number_unique');
        });

        Schema::create('whatsapp_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresa')->cascadeOnDelete();
            $table->foreignId('whatsapp_integration_id')->nullable()->constrained('whatsapp_integrations')->nullOnDelete();
            $table->string('name', 160);
            $table->string('message_type', 30)->default('freeform');
            $table->text('body_text')->nullable();
            $table->string('media_url', 2048)->nullable();
            $table->string('meta_template_name', 160)->nullable();
            $table->string('template_language_code', 20)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('last_processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'scheduled_at']);
        });

        Schema::create('whatsapp_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_campaign_id')->constrained('whatsapp_campaigns')->cascadeOnDelete();
            $table->foreignId('whatsapp_contact_id')->nullable()->constrained('whatsapp_contacts')->nullOnDelete();
            $table->string('recipient_name', 160);
            $table->string('recipient_number', 40);
            $table->string('recipient_number_e164', 25);
            $table->string('send_mode', 30)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('meta_message_id', 191)->nullable();
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_campaign_id', 'status']);
            $table->index(['recipient_number_e164']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_campaign_recipients');
        Schema::dropIfExists('whatsapp_campaigns');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_integrations');
    }
};
