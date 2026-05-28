<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\WhatsAppCampaignsPanel;
use App\Models\Empresa;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppIntegration;
use App\Support\EmpresaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppCampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_media_whatsapp_page_renders_dedicated_panel(): void
    {
        $empresa = $this->createEmpresa('11.222.333/0001-44', 'Empresa WhatsApp', 'tok-wpp');
        $user = User::factory()->create([
            'cpf' => '11122233344',
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_REDE_SOCIAL],
        ]);

        $response = $this->actingAs($user)->get(route('admin.social-media.whatsapp.index'));

        $response
            ->assertOk()
            ->assertSee('WhatsApp')
            ->assertSeeLivewire(WhatsAppCampaignsPanel::class);
    }

    public function test_user_can_configure_whatsapp_integration_contacts_and_campaign(): void
    {
        $empresa = $this->createEmpresa('11.222.333/0001-45', 'Empresa WhatsApp Config', 'tok-wpp-config');
        $user = User::factory()->create([
            'cpf' => '11122233345',
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_REDE_SOCIAL],
        ]);

        $this->actingAs($user);

        Livewire::test(WhatsAppCampaignsPanel::class)
            ->set('metaBusinessAccountId', 'waba-1')
            ->set('metaPhoneNumberId', 'phone-1')
            ->set('displayPhoneNumber', '+55 65 99999-0000')
            ->set('accessToken', 'token-permanente')
            ->call('saveIntegration')
            ->assertSee('Integracao WhatsApp salva com sucesso.')
            ->set('contactName', 'Maria Cliente')
            ->set('contactWhatsappNumber', '65999990000')
            ->set('contactOptInWhatsapp', true)
            ->set('contactOptInSource', 'site')
            ->call('saveContact')
            ->assertSee('Contato WhatsApp criado com sucesso.');

        $contact = WhatsAppContact::query()->firstOrFail();

        Livewire::test(WhatsAppCampaignsPanel::class)
            ->set('campaignName', 'Campanha Loja')
            ->set('campaignMessageType', 'template')
            ->set('campaignTemplateName', 'hello_world')
            ->set('campaignTemplateLanguageCode', 'pt_BR')
            ->set('selectedContactIds', [$contact->id])
            ->call('saveCampaign')
            ->assertSee('Campanha WhatsApp criada com sucesso.');

        $this->assertDatabaseHas('whatsapp_integrations', [
            'empresa_id' => $empresa->id,
            'status' => 'connected',
            'meta_phone_number_id' => 'phone-1',
        ]);

        $this->assertDatabaseHas('whatsapp_contacts', [
            'empresa_id' => $empresa->id,
            'name' => 'Maria Cliente',
            'whatsapp_number_e164' => '+5565999990000',
            'opt_in_whatsapp' => true,
        ]);

        $this->assertDatabaseHas('whatsapp_campaigns', [
            'empresa_id' => $empresa->id,
            'name' => 'Campanha Loja',
            'message_type' => 'template',
            'status' => 'draft',
        ]);
    }

    public function test_dispatch_now_sends_template_and_updates_status(): void
    {
        $empresa = $this->createEmpresa('11.222.333/0001-46', 'Empresa WhatsApp Envio', 'tok-wpp-send');

        $integration = WhatsAppIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'status' => 'connected',
            'meta_business_account_id' => 'waba-2',
            'meta_phone_number_id' => 'phone-2',
            'display_phone_number' => '+55 65 99999-1000',
            'access_token' => 'token-valido',
        ]);

        $contact = WhatsAppContact::query()->create([
            'empresa_id' => $empresa->id,
            'name' => 'Joao Cliente',
            'whatsapp_number' => '65999991000',
            'whatsapp_number_e164' => '+5565999991000',
            'opt_in_whatsapp' => true,
            'opt_in_whatsapp_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $campaign = WhatsAppCampaign::query()->create([
            'empresa_id' => $empresa->id,
            'whatsapp_integration_id' => $integration->id,
            'name' => 'Campanha Template',
            'message_type' => 'template',
            'meta_template_name' => 'hello_world',
            'template_language_code' => 'pt_BR',
            'status' => 'draft',
        ]);

        $campaign->recipients()->create([
            'whatsapp_contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_number' => $contact->whatsapp_number,
            'recipient_number_e164' => $contact->whatsapp_number_e164,
            'status' => 'pending',
        ]);

        $user = User::factory()->create([
            'cpf' => '11122233346',
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_REDE_SOCIAL],
        ]);

        Http::fake([
            'https://graph.facebook.com/*/phone-2/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid-1'],
                ],
            ], 200),
        ]);

        $this->actingAs($user);

        Livewire::test(WhatsAppCampaignsPanel::class)
            ->call('dispatchCampaignNow', $campaign->id)
            ->assertSee('Campanha WhatsApp processada.');

        $this->assertDatabaseHas('whatsapp_campaign_recipients', [
            'whatsapp_campaign_id' => $campaign->id,
            'status' => 'sent',
            'meta_message_id' => 'wamid-1',
            'send_mode' => 'template',
        ]);

        $this->assertDatabaseHas('whatsapp_campaigns', [
            'id' => $campaign->id,
            'status' => 'completed',
        ]);
    }

    public function test_scheduler_processes_due_whatsapp_campaigns(): void
    {
        Carbon::setTestNow('2026-05-28 15:00:00');

        $empresa = $this->createEmpresa('11.222.333/0001-47', 'Empresa WhatsApp Scheduler', 'tok-wpp-scheduler');

        $integration = WhatsAppIntegration::query()->create([
            'empresa_id' => $empresa->id,
            'status' => 'connected',
            'meta_business_account_id' => 'waba-3',
            'meta_phone_number_id' => 'phone-3',
            'display_phone_number' => '+55 65 99999-2000',
            'access_token' => 'token-valido',
        ]);

        $contact = WhatsAppContact::query()->create([
            'empresa_id' => $empresa->id,
            'name' => 'Ana Cliente',
            'whatsapp_number' => '65999992000',
            'whatsapp_number_e164' => '+5565999992000',
            'opt_in_whatsapp' => true,
            'opt_in_whatsapp_at' => now()->subDays(2),
            'status' => 'active',
        ]);

        $campaign = WhatsAppCampaign::query()->create([
            'empresa_id' => $empresa->id,
            'whatsapp_integration_id' => $integration->id,
            'name' => 'Campanha Agendada',
            'message_type' => 'template',
            'meta_template_name' => 'hello_world',
            'template_language_code' => 'pt_BR',
            'scheduled_at' => now()->subMinute(),
            'status' => 'scheduled',
        ]);

        WhatsAppCampaignRecipient::query()->create([
            'whatsapp_campaign_id' => $campaign->id,
            'whatsapp_contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_number' => $contact->whatsapp_number,
            'recipient_number_e164' => $contact->whatsapp_number_e164,
            'status' => 'pending',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/phone-3/messages' => Http::response([
                'messages' => [
                    ['id' => 'wamid-2'],
                ],
            ], 200),
        ]);

        $this->artisan('whatsapp:dispatch-scheduled')
            ->expectsOutput('1 campanha(s) de WhatsApp processada(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('whatsapp_campaign_recipients', [
            'whatsapp_campaign_id' => $campaign->id,
            'status' => 'sent',
            'meta_message_id' => 'wamid-2',
        ]);

        Carbon::setTestNow();
    }

    private function createEmpresa(string $cnpjCpf, string $nome, string $token): Empresa
    {
        return Empresa::query()->create([
            'cnpj_cpf' => $cnpjCpf,
            'nome' => $nome,
            'fantasia' => $nome,
            'razaosocial' => $nome.' LTDA',
            'urlimagem' => 'empresa.png',
            'codigo' => substr(preg_replace('/\D/', '', $cnpjCpf), 0, 4),
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_pad($token, 60, 'x'),
        ]);
    }
}
