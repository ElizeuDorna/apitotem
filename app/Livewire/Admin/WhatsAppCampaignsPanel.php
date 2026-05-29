<?php

namespace App\Livewire\Admin;

use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppContact;
use App\Services\WhatsAppEmbeddedSignupService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WhatsAppCampaignsPanel extends Component
{
    public ?int $editingContactId = null;

    public ?int $editingCampaignId = null;

    public string $metaBusinessAccountId = '';

    public string $metaPhoneNumberId = '';

    public string $displayPhoneNumber = '';

    public string $accessToken = '';

    public string $accessTokenExpiresAt = '';

    public string $contactName = '';

    public string $contactWhatsappNumber = '';

    public bool $contactOptInWhatsapp = false;

    public string $contactOptInWhatsappAt = '';

    public string $contactOptInSource = '';

    public string $contactStatus = 'active';

    public string $campaignName = '';

    public string $campaignMessageType = 'freeform';

    public string $campaignBodyText = '';

    public string $campaignMediaUrl = '';

    public string $campaignTemplateName = '';

    public string $campaignTemplateLanguageCode = 'pt_BR';

    public string $campaignScheduledAt = '';

    public array $selectedContactIds = [];

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $integration = $whatsAppService->integrationForUser($user);
        if (! $integration) {
            return;
        }

        $this->metaBusinessAccountId = (string) ($integration->meta_business_account_id ?? '');
        $this->metaPhoneNumberId = (string) ($integration->meta_phone_number_id ?? '');
        $this->displayPhoneNumber = (string) ($integration->display_phone_number ?? '');
        $this->accessTokenExpiresAt = optional($integration->access_token_expires_at)->format('Y-m-d\TH:i') ?? '';
    }

    public function saveIntegration(WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $whatsAppService->saveIntegrationForUser($user, [
            'meta_business_account_id' => $this->metaBusinessAccountId,
            'meta_phone_number_id' => $this->metaPhoneNumberId,
            'display_phone_number' => $this->displayPhoneNumber,
            'access_token' => $this->accessToken,
            'access_token_expires_at' => $this->accessTokenExpiresAt !== '' ? $this->accessTokenExpiresAt : null,
        ]);

        $this->statusMessage = 'Integracao WhatsApp salva com sucesso.';
        $this->errorMessage = null;
        $this->accessToken = '';
    }

    public function disconnectIntegration(WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $whatsAppService->disconnectIntegrationForUser($user);

        $this->metaBusinessAccountId = '';
        $this->metaPhoneNumberId = '';
        $this->displayPhoneNumber = '';
        $this->accessToken = '';
        $this->accessTokenExpiresAt = '';
        $this->statusMessage = 'Integracao WhatsApp desconectada.';
        $this->errorMessage = null;
    }

    public function saveContact(WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $contact = $this->editingContactId
            ? $whatsAppService->contactsQueryForUser($user)->findOrFail($this->editingContactId)
            : null;

        $whatsAppService->saveContactForUser($user, [
            'name' => $this->contactName,
            'whatsapp_number' => $this->contactWhatsappNumber,
            'opt_in_whatsapp' => $this->contactOptInWhatsapp,
            'opt_in_whatsapp_at' => $this->contactOptInWhatsappAt !== '' ? $this->contactOptInWhatsappAt : null,
            'opt_in_source' => $this->contactOptInSource,
            'status' => $this->contactStatus,
        ], $contact);

        $this->resetContactForm();
        $this->statusMessage = $contact ? 'Contato WhatsApp atualizado com sucesso.' : 'Contato WhatsApp criado com sucesso.';
        $this->errorMessage = null;
    }

    public function editContact(int $contactId, WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $contact = $whatsAppService->contactsQueryForUser($user)->findOrFail($contactId);

        $this->editingContactId = $contact->id;
        $this->contactName = (string) $contact->name;
        $this->contactWhatsappNumber = (string) $contact->whatsapp_number;
        $this->contactOptInWhatsapp = (bool) $contact->opt_in_whatsapp;
        $this->contactOptInWhatsappAt = optional($contact->opt_in_whatsapp_at)->format('Y-m-d\TH:i') ?? '';
        $this->contactOptInSource = (string) ($contact->opt_in_source ?? '');
        $this->contactStatus = (string) ($contact->status ?? 'active');
        $this->statusMessage = null;
        $this->errorMessage = null;
    }

    public function deleteContact(int $contactId, WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $contact = $whatsAppService->contactsQueryForUser($user)->findOrFail($contactId);
        $whatsAppService->deleteContactForUser($user, $contact);

        if ($this->editingContactId === $contactId) {
            $this->resetContactForm();
        }

        $this->statusMessage = 'Contato WhatsApp removido com sucesso.';
        $this->errorMessage = null;
    }

    public function saveCampaign(WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $campaign = $this->editingCampaignId
            ? $whatsAppService->campaignsQueryForUser($user)->findOrFail($this->editingCampaignId)
            : null;

        $whatsAppService->saveCampaignForUser($user, [
            'name' => $this->campaignName,
            'message_type' => $this->campaignMessageType,
            'body_text' => $this->campaignBodyText,
            'media_url' => $this->campaignMediaUrl,
            'meta_template_name' => $this->campaignTemplateName,
            'template_language_code' => $this->campaignTemplateLanguageCode,
            'scheduled_at' => $this->campaignScheduledAt !== '' ? $this->campaignScheduledAt : null,
            'selected_contact_ids' => $this->selectedContactIds,
        ], $campaign);

        $this->resetCampaignForm();
        $this->statusMessage = $campaign ? 'Campanha WhatsApp atualizada com sucesso.' : 'Campanha WhatsApp criada com sucesso.';
        $this->errorMessage = null;
    }

    public function editCampaign(int $campaignId, WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $campaign = $whatsAppService->campaignsQueryForUser($user)->findOrFail($campaignId);

        $this->editingCampaignId = $campaign->id;
        $this->campaignName = (string) $campaign->name;
        $this->campaignMessageType = (string) $campaign->message_type;
        $this->campaignBodyText = (string) ($campaign->body_text ?? '');
        $this->campaignMediaUrl = (string) ($campaign->media_url ?? '');
        $this->campaignTemplateName = (string) ($campaign->meta_template_name ?? '');
        $this->campaignTemplateLanguageCode = (string) ($campaign->template_language_code ?? 'pt_BR');
        $this->campaignScheduledAt = optional($campaign->scheduled_at)->format('Y-m-d\TH:i') ?? '';
        $this->selectedContactIds = $campaign->recipients->pluck('whatsapp_contact_id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $this->statusMessage = null;
        $this->errorMessage = null;
    }

    public function deleteCampaign(int $campaignId, WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $campaign = $whatsAppService->campaignsQueryForUser($user)->findOrFail($campaignId);
        $whatsAppService->deleteCampaignForUser($user, $campaign);

        if ($this->editingCampaignId === $campaignId) {
            $this->resetCampaignForm();
        }

        $this->statusMessage = 'Campanha WhatsApp removida com sucesso.';
        $this->errorMessage = null;
    }

    public function dispatchCampaignNow(int $campaignId, WhatsAppService $whatsAppService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $campaign = $whatsAppService->campaignsQueryForUser($user)->findOrFail($campaignId);
        $whatsAppService->dispatchCampaign($campaign, true);

        $this->statusMessage = 'Campanha WhatsApp processada.';
        $this->errorMessage = null;
    }

    public function render(WhatsAppService $whatsAppService, WhatsAppEmbeddedSignupService $embeddedSignupService)
    {
        $user = Auth::user();
        abort_unless($user?->hasMenuAccess('rede_social'), 403);

        $integration = $whatsAppService->integrationForUser($user);
        $contacts = $whatsAppService->contactsQueryForUser($user)->get();
        $campaigns = $whatsAppService->campaignsQueryForUser($user)->get();

        $embeddedSignupMissingKeys = $embeddedSignupService->missingConfigurationKeys();

        return view('livewire.admin.whatsapp-campaigns-panel', [
            'integration' => $integration,
            'contacts' => $contacts,
            'campaigns' => $campaigns,
            'embeddedSignupConfigured' => $embeddedSignupMissingKeys === [],
            'embeddedSignupMissingKeys' => $embeddedSignupMissingKeys,
            'embeddedSignupUrl' => route('admin.social-media.whatsapp.embedded-signup.show'),
        ]);
    }

    private function resetContactForm(): void
    {
        $this->reset([
            'editingContactId',
            'contactName',
            'contactWhatsappNumber',
            'contactOptInWhatsapp',
            'contactOptInWhatsappAt',
            'contactOptInSource',
        ]);
        $this->contactStatus = 'active';
        $this->resetValidation();
    }

    private function resetCampaignForm(): void
    {
        $this->reset([
            'editingCampaignId',
            'campaignName',
            'campaignBodyText',
            'campaignMediaUrl',
            'campaignTemplateName',
            'campaignScheduledAt',
            'selectedContactIds',
        ]);
        $this->campaignMessageType = 'freeform';
        $this->campaignTemplateLanguageCode = 'pt_BR';
        $this->resetValidation();
    }
}
