<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppIntegration;
use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WhatsAppService
{
    public function __construct(private readonly WhatsAppGraphService $graphService)
    {
    }

    public function empresaForUser(User $user): Empresa
    {
        $empresaId = EmpresaContext::requireEmpresaId($user);

        return Empresa::query()->findOrFail($empresaId);
    }

    public function integrationForUser(User $user): ?WhatsAppIntegration
    {
        return WhatsAppIntegration::query()
            ->where('empresa_id', EmpresaContext::requireEmpresaId($user))
            ->first();
    }

    public function contactsQueryForUser(User $user): Builder
    {
        return WhatsAppContact::query()
            ->where('empresa_id', EmpresaContext::requireEmpresaId($user))
            ->orderBy('name');
    }

    public function campaignsQueryForUser(User $user): Builder
    {
        return WhatsAppCampaign::query()
            ->where('empresa_id', EmpresaContext::requireEmpresaId($user))
            ->with(['recipients.contact', 'integration'])
            ->latest('id');
    }

    public function saveIntegrationForUser(User $user, array $data): WhatsAppIntegration
    {
        $empresaId = EmpresaContext::requireEmpresaId($user);

        $validated = Validator::make($data, [
            'meta_business_account_id' => ['nullable', 'string', 'max:120'],
            'meta_phone_number_id' => ['required', 'string', 'max:120'],
            'display_phone_number' => ['nullable', 'string', 'max:60'],
            'access_token' => ['required', 'string'],
            'access_token_expires_at' => ['nullable', 'date'],
        ], [
            'meta_phone_number_id.required' => 'Informe o Phone Number ID da Meta.',
            'access_token.required' => 'Informe o Access Token permanente da Meta.',
        ])->validate();

        return WhatsAppIntegration::query()->updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'status' => 'connected',
                'meta_business_account_id' => $validated['meta_business_account_id'] ?? null,
                'meta_phone_number_id' => $validated['meta_phone_number_id'],
                'display_phone_number' => $validated['display_phone_number'] ?? null,
                'access_token' => $validated['access_token'],
                'access_token_expires_at' => $validated['access_token_expires_at'] ?? null,
                'last_synced_at' => now(),
                'last_error' => null,
            ]
        );
    }

    public function disconnectIntegrationForUser(User $user): void
    {
        $empresaId = EmpresaContext::requireEmpresaId($user);

        WhatsAppIntegration::query()
            ->where('empresa_id', $empresaId)
            ->update([
                'status' => 'disconnected',
                'meta_business_account_id' => null,
                'meta_phone_number_id' => null,
                'display_phone_number' => null,
                'access_token' => null,
                'access_token_expires_at' => null,
                'last_synced_at' => now(),
                'last_error' => null,
            ]);
    }

    public function saveContactForUser(User $user, array $data, ?WhatsAppContact $contact = null): WhatsAppContact
    {
        $empresaId = EmpresaContext::requireEmpresaId($user);
        $normalizedNumber = $this->normalizePhoneNumber((string) ($data['whatsapp_number'] ?? ''));

        if (! $normalizedNumber) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Informe um numero de WhatsApp valido. Use DDI + DDD quando possivel.',
            ]);
        }

        $validated = Validator::make(array_merge($data, ['whatsapp_number_e164' => $normalizedNumber]), [
            'name' => ['required', 'string', 'max:160'],
            'whatsapp_number' => ['required', 'string', 'max:40'],
            'whatsapp_number_e164' => [
                'required',
                'string',
                'max:25',
                Rule::unique('whatsapp_contacts', 'whatsapp_number_e164')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($contact?->id),
            ],
            'opt_in_whatsapp' => ['required', 'boolean'],
            'opt_in_whatsapp_at' => ['nullable', 'date'],
            'opt_in_source' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'name.required' => 'Informe o nome do contato.',
        ])->validate();

        return WhatsAppContact::query()->updateOrCreate(
            [
                'id' => $contact?->id,
            ],
            [
                'empresa_id' => $empresaId,
                'name' => $validated['name'],
                'whatsapp_number' => $validated['whatsapp_number'],
                'whatsapp_number_e164' => $validated['whatsapp_number_e164'],
                'opt_in_whatsapp' => (bool) $validated['opt_in_whatsapp'],
                'opt_in_whatsapp_at' => (bool) $validated['opt_in_whatsapp']
                    ? ($validated['opt_in_whatsapp_at'] ?? now())
                    : null,
                'opt_in_source' => $validated['opt_in_source'] ?? null,
                'status' => $validated['status'],
            ]
        );
    }

    public function deleteContactForUser(User $user, WhatsAppContact $contact): void
    {
        $this->assertContactOwnership($user, $contact);
        $contact->delete();
    }

    public function saveCampaignForUser(User $user, array $data, ?WhatsAppCampaign $campaign = null): WhatsAppCampaign
    {
        $empresaId = EmpresaContext::requireEmpresaId($user);
        $integration = $this->integrationForUser($user);

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:160'],
            'message_type' => ['required', Rule::in(['freeform', 'template'])],
            'body_text' => ['nullable', 'string', 'max:4000'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'meta_template_name' => ['nullable', 'string', 'max:160'],
            'template_language_code' => ['nullable', 'string', 'max:20'],
            'scheduled_at' => ['nullable', 'date'],
            'selected_contact_ids' => ['required', 'array', 'min:1'],
            'selected_contact_ids.*' => ['integer'],
        ], [
            'name.required' => 'Informe o nome da campanha.',
            'selected_contact_ids.min' => 'Selecione ao menos um contato.',
        ])->after(function ($validator) use ($data) {
            $messageType = (string) ($data['message_type'] ?? 'freeform');
            if ($messageType === 'freeform' && blank($data['body_text'] ?? null) && blank($data['media_url'] ?? null)) {
                $validator->errors()->add('body_text', 'Informe uma mensagem ou uma imagem para o disparo livre.');
            }
            if ($messageType === 'template' && blank($data['meta_template_name'] ?? null)) {
                $validator->errors()->add('meta_template_name', 'Informe o nome do template aprovado na Meta.');
            }
            if ($messageType === 'template' && blank($data['template_language_code'] ?? null)) {
                $validator->errors()->add('template_language_code', 'Informe o idioma do template aprovado.');
            }
        })->validate();

        $contacts = WhatsAppContact::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('id', $validated['selected_contact_ids'])
            ->get();

        if ($contacts->count() !== count($validated['selected_contact_ids'])) {
            throw ValidationException::withMessages([
                'selected_contact_ids' => 'Um ou mais contatos selecionados nao pertencem a empresa ativa.',
            ]);
        }

        return DB::transaction(function () use ($campaign, $contacts, $empresaId, $integration, $validated) {
            $savedCampaign = WhatsAppCampaign::query()->updateOrCreate(
                ['id' => $campaign?->id],
                [
                    'empresa_id' => $empresaId,
                    'whatsapp_integration_id' => $integration?->id,
                    'name' => $validated['name'],
                    'message_type' => $validated['message_type'],
                    'body_text' => $validated['body_text'] ?? null,
                    'media_url' => $validated['media_url'] ?? null,
                    'meta_template_name' => $validated['meta_template_name'] ?? null,
                    'template_language_code' => $validated['template_language_code'] ?? null,
                    'scheduled_at' => $validated['scheduled_at'] ?? null,
                    'status' => ($validated['scheduled_at'] ?? null) ? 'scheduled' : 'draft',
                    'last_error' => null,
                ]
            );

            $savedCampaign->recipients()->delete();

            foreach ($contacts as $contact) {
                $savedCampaign->recipients()->create([
                    'whatsapp_contact_id' => $contact->id,
                    'recipient_name' => $contact->name,
                    'recipient_number' => $contact->whatsapp_number,
                    'recipient_number_e164' => $contact->whatsapp_number_e164,
                    'send_mode' => $this->resolveSendMode($savedCampaign, $contact),
                    'status' => 'pending',
                ]);
            }

            return $savedCampaign->fresh(['recipients.contact', 'integration']);
        });
    }

    public function deleteCampaignForUser(User $user, WhatsAppCampaign $campaign): void
    {
        $this->assertCampaignOwnership($user, $campaign);
        $campaign->delete();
    }

    public function dispatchCampaign(WhatsAppCampaign $campaign, bool $force = false): void
    {
        $campaign->loadMissing(['integration', 'recipients.contact']);

        if (! $campaign->integration) {
            throw new RuntimeException('Configure a integracao WhatsApp antes de disparar a campanha.');
        }

        if (! $force && $campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            return;
        }

        $campaign->update([
            'status' => 'processing',
            'last_error' => null,
            'last_processed_at' => now(),
        ]);

        foreach ($campaign->recipients as $recipient) {
            if (! in_array((string) $recipient->status, ['pending', 'failed'], true)) {
                continue;
            }

            $this->dispatchRecipient($campaign, $recipient);
        }

        $hasFailures = $campaign->recipients()->where('status', 'failed')->exists();
        $hasSent = $campaign->recipients()->whereIn('status', ['sent', 'delivered', 'read'])->exists();

        $campaign->update([
            'status' => $hasFailures && ! $hasSent ? 'failed' : 'completed',
            'last_processed_at' => now(),
        ]);
    }

    public function dispatchScheduledCampaigns(): int
    {
        $campaigns = WhatsAppCampaign::query()
            ->with(['integration', 'recipients.contact'])
            ->whereIn('status', ['scheduled', 'failed'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $processed = 0;

        foreach ($campaigns as $campaign) {
            $this->dispatchCampaign($campaign);
            $processed++;
        }

        return $processed;
    }

    public function processWebhookPayload(array $payload): void
    {
        foreach ((array) Arr::get($payload, 'entry', []) as $entry) {
            foreach ((array) Arr::get($entry, 'changes', []) as $change) {
                $value = (array) Arr::get($change, 'value', []);
                $phoneNumberId = (string) Arr::get($value, 'metadata.phone_number_id', '');

                foreach ((array) Arr::get($value, 'statuses', []) as $status) {
                    $this->applyStatusUpdate($phoneNumberId, (array) $status);
                }

                foreach ((array) Arr::get($value, 'messages', []) as $message) {
                    $this->applyInboundMessage($phoneNumberId, (array) $message);
                }
            }
        }
    }

    public function canSendFreeform(?Carbon $lastInboundAt): bool
    {
        return $lastInboundAt !== null && $lastInboundAt->greaterThanOrEqualTo(now()->subHours(24));
    }

    public function normalizePhoneNumber(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55') && in_array(strlen($digits), [12, 13], true)) {
            return '+'.$digits;
        }

        if (in_array(strlen($digits), [10, 11], true)) {
            return '+55'.$digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return '+'.$digits;
        }

        return null;
    }

    private function dispatchRecipient(WhatsAppCampaign $campaign, WhatsAppCampaignRecipient $recipient): void
    {
        $contact = $recipient->contact;
        $mode = $this->resolveSendMode($campaign, $contact);

        $recipient->update([
            'send_mode' => $mode,
            'last_attempt_at' => now(),
            'error_code' => null,
            'error_message' => null,
        ]);

        if (! $contact || ! $contact->opt_in_whatsapp || $contact->status !== 'active') {
            $recipient->update([
                'status' => 'skipped',
                'error_message' => 'Contato sem opt-in ativo para WhatsApp.',
                'failed_at' => now(),
            ]);
            return;
        }

        if ($mode === 'unavailable') {
            $recipient->update([
                'status' => 'failed',
                'error_message' => 'Contato fora da janela de 24 horas. Use template aprovado para este destinatario.',
                'failed_at' => now(),
            ]);
            return;
        }

        try {
            if ($mode === 'template') {
                $response = $this->graphService->sendTemplateMessage(
                    $campaign->integration,
                    $recipient->recipient_number_e164,
                    (string) $campaign->meta_template_name,
                    (string) $campaign->template_language_code
                );
            } else {
                $response = $this->graphService->sendFreeformMessage(
                    $campaign->integration,
                    $recipient->recipient_number_e164,
                    (string) ($campaign->body_text ?? ''),
                    $campaign->media_url
                );
            }

            $recipient->update([
                'status' => 'sent',
                'meta_message_id' => Arr::get($response, 'messages.0.id'),
                'sent_at' => now(),
                'failed_at' => null,
            ]);
        } catch (RuntimeException $exception) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

            $campaign->update([
                'last_error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveSendMode(WhatsAppCampaign $campaign, ?WhatsAppContact $contact): string
    {
        if ($campaign->message_type === 'template') {
            return 'template';
        }

        return $this->canSendFreeform($contact?->last_whatsapp_inbound_at) ? 'freeform' : 'unavailable';
    }

    private function applyStatusUpdate(string $phoneNumberId, array $status): void
    {
        if ($phoneNumberId === '') {
            return;
        }

        $recipient = WhatsAppCampaignRecipient::query()
            ->where('meta_message_id', (string) Arr::get($status, 'id', ''))
            ->first();

        if (! $recipient) {
            return;
        }

        $mappedStatus = match ((string) Arr::get($status, 'status', '')) {
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
            default => null,
        };

        if (! $mappedStatus) {
            return;
        }

        $payload = ['status' => $mappedStatus];

        if ($mappedStatus === 'delivered') {
            $payload['delivered_at'] = now();
        }
        if ($mappedStatus === 'read') {
            $payload['read_at'] = now();
        }
        if ($mappedStatus === 'failed') {
            $payload['failed_at'] = now();
            $payload['error_code'] = (string) Arr::get($status, 'errors.0.code', '');
            $payload['error_message'] = (string) Arr::get($status, 'errors.0.title', 'Falha no envio WhatsApp.');
        }

        $recipient->update($payload);
    }

    private function applyInboundMessage(string $phoneNumberId, array $message): void
    {
        if ($phoneNumberId === '') {
            return;
        }

        $number = $this->normalizePhoneNumber((string) Arr::get($message, 'from', ''));
        if (! $number) {
            return;
        }

        $integration = WhatsAppIntegration::query()
            ->where('meta_phone_number_id', $phoneNumberId)
            ->first();

        if (! $integration) {
            return;
        }

        $contact = WhatsAppContact::query()
            ->where('empresa_id', $integration->empresa_id)
            ->where('whatsapp_number_e164', $number)
            ->first();

        if (! $contact) {
            return;
        }

        $contact->update([
            'last_whatsapp_inbound_at' => now(),
        ]);
    }

    private function assertContactOwnership(User $user, WhatsAppContact $contact): void
    {
        abort_unless((int) $contact->empresa_id === EmpresaContext::requireEmpresaId($user), 403);
    }

    private function assertCampaignOwnership(User $user, WhatsAppCampaign $campaign): void
    {
        abort_unless((int) $campaign->empresa_id === EmpresaContext::requireEmpresaId($user), 403);
    }
}
