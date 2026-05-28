<?php

namespace App\Services;

use App\Models\WhatsAppIntegration;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WhatsAppGraphService
{
    public function isConfigured(): bool
    {
        return $this->version() !== '';
    }

    public function sendFreeformMessage(WhatsAppIntegration $integration, string $to, string $body, ?string $mediaUrl = null): array
    {
        $this->assertIntegrationReady($integration);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
        ];

        if ($mediaUrl) {
            $payload['type'] = 'image';
            $payload['image'] = [
                'link' => $mediaUrl,
                'caption' => $body !== '' ? $body : null,
            ];
        } else {
            $payload['type'] = 'text';
            $payload['text'] = [
                'preview_url' => true,
                'body' => $body,
            ];
        }

        return $this->postMessage($integration, array_filter($payload, function ($value) {
            return $value !== null;
        }));
    }

    public function sendTemplateMessage(WhatsAppIntegration $integration, string $to, string $templateName, string $languageCode): array
    {
        $this->assertIntegrationReady($integration);

        return $this->postMessage($integration, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ]);
    }

    public function verifyWebhook(string $verifyToken, ?string $mode, ?string $token, ?string $challenge): ?string
    {
        if ($mode !== 'subscribe' || $token !== $verifyToken) {
            return null;
        }

        return $challenge;
    }

    private function postMessage(WhatsAppIntegration $integration, array $payload): array
    {
        try {
            $response = Http::asJson()
                ->withToken((string) $integration->access_token)
                ->post($this->baseUrl().'/'.$integration->meta_phone_number_id.'/messages', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new RuntimeException($this->humanizeException($exception), previous: $exception);
        } catch (Throwable $exception) {
            throw new RuntimeException('Falha ao enviar mensagem pelo WhatsApp.', previous: $exception);
        }

        return is_array($response) ? $response : [];
    }

    private function assertIntegrationReady(WhatsAppIntegration $integration): void
    {
        if ((string) $integration->status !== 'connected') {
            throw new RuntimeException('Integracao WhatsApp desconectada para a empresa selecionada.');
        }

        if ((string) $integration->meta_phone_number_id === '' || (string) $integration->access_token === '') {
            throw new RuntimeException('Preencha o Phone Number ID e o Access Token da integracao WhatsApp.');
        }
    }

    private function baseUrl(): string
    {
        return 'https://graph.facebook.com/'.$this->version();
    }

    private function version(): string
    {
        return (string) config('services.whatsapp_graph.version', 'v25.0');
    }

    private function humanizeException(RequestException $exception): string
    {
        $response = $exception->response;
        $message = (string) Arr::get($response?->json(), 'error.message', 'Falha ao enviar mensagem pelo WhatsApp.');

        return $message !== '' ? $message : 'Falha ao enviar mensagem pelo WhatsApp.';
    }
}
