<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsAppIntegration;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class WhatsAppEmbeddedSignupService
{
    public function __construct(private readonly WhatsAppService $whatsAppService)
    {
    }

    public function isConfigured(): bool
    {
        return $this->missingConfigurationKeys() === [];
    }

    public function missingConfigurationKeys(): array
    {
        $missing = [];

        if ($this->appId() === '') {
            $missing[] = 'META_APP_ID';
        }

        if ($this->appSecret() === '') {
            $missing[] = 'META_APP_SECRET';
        }

        if ($this->configurationId() === '') {
            $missing[] = 'WHATSAPP_EMBEDDED_SIGNUP_CONFIGURATION_ID';
        }

        return $missing;
    }

    public function appId(): string
    {
        return trim((string) config('services.whatsapp_graph.app_id'));
    }

    public function configurationId(): string
    {
        return trim((string) config('services.whatsapp_graph.embedded_signup_configuration_id'));
    }

    public function graphVersion(): string
    {
        return trim((string) config('services.whatsapp_graph.version', 'v25.0'));
    }

    public function onboardForUser(User $user, array $data): WhatsAppIntegration
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Configure META_APP_ID, META_APP_SECRET e WHATSAPP_EMBEDDED_SIGNUP_CONFIGURATION_ID antes de usar o onboarding da Meta.');
        }

        $validated = Validator::make($data, [
            'code' => ['required', 'string'],
            'waba_id' => ['required', 'string', 'max:120'],
            'phone_number_id' => ['required', 'string', 'max:120'],
            'business_id' => ['nullable', 'string', 'max:120'],
            'two_step_verification_pin' => ['required', 'digits:6'],
        ], [
            'code.required' => 'A Meta nao retornou um codigo de autorizacao valido.',
            'waba_id.required' => 'A Meta nao retornou o WABA ID da empresa.',
            'phone_number_id.required' => 'A Meta nao retornou o Phone Number ID da empresa.',
            'two_step_verification_pin.required' => 'Informe um PIN de 6 digitos para registrar o numero da empresa.',
            'two_step_verification_pin.digits' => 'O PIN do WhatsApp precisa ter exatamente 6 digitos.',
        ])->validate();

        try {
            $businessToken = $this->exchangeCodeForBusinessToken($validated['code']);
            $this->subscribeAppToWaba($validated['waba_id'], $businessToken);
            $this->registerPhoneNumber($validated['phone_number_id'], $businessToken, $validated['two_step_verification_pin']);
            $phoneProfile = $this->fetchPhoneNumberProfile($validated['phone_number_id'], $businessToken);
        } catch (Throwable $exception) {
            throw new RuntimeException($this->humanizeMetaException($exception), previous: $exception);
        }

        return $this->whatsAppService->saveIntegrationForUser($user, [
            'meta_business_account_id' => $validated['waba_id'],
            'meta_phone_number_id' => $validated['phone_number_id'],
            'display_phone_number' => (string) Arr::get($phoneProfile, 'display_phone_number', ''),
            'access_token' => $businessToken,
            'access_token_expires_at' => null,
        ]);
    }

    private function exchangeCodeForBusinessToken(string $code): string
    {
        $response = Http::get($this->baseUrl('/oauth/access_token'), [
            'client_id' => $this->appId(),
            'client_secret' => $this->appSecret(),
            'code' => $code,
        ])->throw();

        $payload = $response->json();
        if (is_array($payload) && trim((string) Arr::get($payload, 'access_token', '')) !== '') {
            return trim((string) Arr::get($payload, 'access_token', ''));
        }

        $rawBody = trim((string) $response->body());
        if ($rawBody !== '' && ! str_starts_with($rawBody, '{')) {
            return $rawBody;
        }

        throw new RuntimeException('A Meta nao retornou um business token valido para concluir o onboarding.');
    }

    private function subscribeAppToWaba(string $wabaId, string $businessToken): void
    {
        try {
            Http::withToken($businessToken)
                ->post($this->baseUrl('/'.$wabaId.'/subscribed_apps'))
                ->throw();
        } catch (Throwable $exception) {
            if ($this->isAlreadyProvisionedException($exception)) {
                return;
            }

            throw $exception;
        }
    }

    private function registerPhoneNumber(string $phoneNumberId, string $businessToken, string $pin): void
    {
        try {
            Http::asJson()
                ->withToken($businessToken)
                ->post($this->baseUrl('/'.$phoneNumberId.'/register'), [
                    'messaging_product' => 'whatsapp',
                    'pin' => $pin,
                ])
                ->throw();
        } catch (Throwable $exception) {
            if ($this->isAlreadyProvisionedException($exception)) {
                return;
            }

            throw $exception;
        }
    }

    private function fetchPhoneNumberProfile(string $phoneNumberId, string $businessToken): array
    {
        $response = Http::withToken($businessToken)
            ->get($this->baseUrl('/'.$phoneNumberId), [
                'fields' => 'display_phone_number,verified_name',
            ])
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    private function appSecret(): string
    {
        return trim((string) config('services.whatsapp_graph.app_secret'));
    }

    private function baseUrl(string $path = ''): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion().$path;
    }

    private function humanizeMetaException(Throwable $exception): string
    {
        if ($exception instanceof RuntimeException && $exception->getMessage() !== '') {
            return $exception->getMessage();
        }

        if ($exception instanceof RequestException) {
            $message = trim((string) Arr::get($exception->response?->json(), 'error.message', ''));

            if ($message !== '') {
                return $message;
            }
        }

        return 'Falha ao concluir o onboarding do WhatsApp na Meta.';
    }

    private function isAlreadyProvisionedException(Throwable $exception): bool
    {
        $message = mb_strtolower($this->humanizeMetaException($exception));

        return str_contains($message, 'already subscribed')
            || str_contains($message, 'already registered')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'ja esta inscrito')
            || str_contains($message, 'ja esta registrado');
    }
}