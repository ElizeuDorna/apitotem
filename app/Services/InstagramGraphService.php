<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Configuracao;
use App\Models\SocialMediaIntegration;
use App\Models\SocialMediaTemplate;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class InstagramGraphService
{
    public function __construct(private readonly SocialMediaTemplateService $templateService)
    {
    }

    public function isConfigured(): bool
    {
        return $this->appId() !== ''
            && $this->appSecret() !== ''
            && $this->redirectUri() !== '';
    }

    public function authorizationUrl(string $state): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Configuracao do Meta/Instagram ausente.');
        }

        return 'https://www.facebook.com/'.$this->version().'/dialog/oauth?'.http_build_query([
            'client_id' => $this->appId(),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => implode(',', [
                'pages_show_list',
                'pages_read_engagement',
                'instagram_basic',
                'instagram_content_publish',
                'business_management',
            ]),
            'response_type' => 'code',
        ]);
    }

    public function connectEmpresa(Empresa $empresa, string $code): SocialMediaIntegration
    {
        $connectionData = $this->beginConnectEmpresa($code);

        return $this->connectEmpresaWithSelection(
            empresa: $empresa,
            selectedAccount: $connectionData['accounts'][0],
            expiresIn: $connectionData['expires_in'],
            metaUserAccessToken: $connectionData['meta_user_access_token'] ?? null,
        );
    }

    public function beginConnectEmpresa(string $code): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Configuracao do Meta/Instagram ausente.');
        }

        try {
            $shortLivedToken = $this->exchangeCode($code);
            $longLivedToken = $this->exchangeForLongLivedToken((string) Arr::get($shortLivedToken, 'access_token', ''));
            $metaUserAccessToken = (string) Arr::get($longLivedToken, 'access_token', '');

            return [
                'expires_in' => (int) Arr::get($longLivedToken, 'expires_in', 0),
                'meta_user_access_token' => $metaUserAccessToken,
                'accounts' => $this->resolveInstagramBusinessAccounts($metaUserAccessToken),
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException($this->humanizeMetaException($exception));
        }
    }

    public function connectEmpresaWithSelection(Empresa $empresa, array $selectedAccount, int $expiresIn, ?string $metaUserAccessToken = null): SocialMediaIntegration
    {
        $payload = [
            'status' => 'connected',
            'instagram_user_id' => (string) Arr::get($selectedAccount, 'instagram_user_id', ''),
            'instagram_username' => (string) Arr::get($selectedAccount, 'instagram_username', ''),
            'instagram_business_account_id' => (string) Arr::get($selectedAccount, 'instagram_business_account_id', ''),
            'facebook_page_id' => (string) Arr::get($selectedAccount, 'facebook_page_id', ''),
            'facebook_page_name' => (string) Arr::get($selectedAccount, 'facebook_page_name', ''),
            'access_token' => (string) Arr::get($selectedAccount, 'access_token', ''),
            'access_token_expires_at' => $this->resolveExpirationDate($expiresIn),
            'last_synced_at' => now(),
            'last_error' => null,
        ];

        if ($this->supportsMetaUserTokenPersistence()) {
            $payload['meta_user_access_token'] = $metaUserAccessToken;
            $payload['meta_user_access_token_expires_at'] = $this->resolveExpirationDate($expiresIn);
        }

        return SocialMediaIntegration::query()->updateOrCreate([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
        ], $payload);
    }

    public function disconnectEmpresa(Empresa $empresa): void
    {
        $payload = [
            'status' => 'disconnected',
            'instagram_user_id' => null,
            'instagram_username' => null,
            'instagram_business_account_id' => null,
            'facebook_page_id' => null,
            'facebook_page_name' => null,
            'access_token' => null,
            'access_token_expires_at' => null,
            'last_synced_at' => now(),
            'last_error' => null,
        ];

        if ($this->supportsMetaUserTokenPersistence()) {
            $payload['meta_user_access_token'] = null;
            $payload['meta_user_access_token_expires_at'] = null;
        }

        SocialMediaIntegration::query()->updateOrCreate([
            'empresa_id' => $empresa->id,
            'provider' => 'instagram_graph',
        ], $payload);
    }

    public function testIntegration(SocialMediaIntegration $integration): array
    {
        $integration = $this->assertIntegrationReady($integration, requireInstagram: true, requireFacebook: false);

        try {
            $instagramAccount = Http::get($this->graphUrl('/'.$integration->instagram_business_account_id), [
                'fields' => 'id,username',
                'access_token' => $integration->access_token,
            ])->throw()->json();

            $pageData = [];
            if ($integration->facebook_page_id) {
                $pageData = Http::get($this->graphUrl('/'.$integration->facebook_page_id), [
                    'fields' => 'id,name',
                    'access_token' => $integration->access_token,
                ])->throw()->json();
            }

            $integration->update([
                'last_synced_at' => now(),
                'last_error' => null,
                'instagram_username' => (string) Arr::get($instagramAccount, 'username', $integration->instagram_username),
                'facebook_page_name' => (string) Arr::get($pageData, 'name', $integration->facebook_page_name),
            ]);

            return [
                'instagram_username' => (string) Arr::get($instagramAccount, 'username', $integration->instagram_username),
                'facebook_page_name' => (string) Arr::get($pageData, 'name', $integration->facebook_page_name),
                'facebook_page_id' => (string) Arr::get($pageData, 'id', $integration->facebook_page_id),
            ];
        } catch (Throwable $exception) {
            $message = $this->humanizeMetaException($exception);
            $this->markIntegrationError($integration, $message, $this->isTokenProblem($exception) ? 'expired' : null);

            throw new RuntimeException($message);
        }
    }

    public function integrationStatusSummary(SocialMediaIntegration $integration): array
    {
        if ($integration->status === 'expired' || ($integration->access_token && $this->isExpired($integration))) {
            return [
                'level' => 'expired',
                'message' => 'Token da Meta expirado. Reconecte a conta desta empresa para voltar a publicar.',
            ];
        }

        if ($integration->status !== 'connected' || ! $integration->access_token) {
            return [
                'level' => 'disconnected',
                'message' => 'Conecte a conta Meta desta empresa para habilitar publicacao no Instagram e Facebook.',
            ];
        }

        return [
            'level' => 'connected',
            'message' => 'Conexao Meta ativa e pronta para publicar no Instagram e Facebook.',
        ];
    }

    public function publishTemplate(SocialMediaTemplate $template): array
    {
        $channels = $this->resolveSelectedChannels($template);

        if ($channels === []) {
            throw new RuntimeException('Selecione ao menos uma rede social para publicar este template.');
        }

        $results = [];
        $errors = [];

        if (in_array('instagram', $channels, true)) {
            try {
                $results['instagram'] = $this->publishTemplateToInstagram($template);
            } catch (Throwable $exception) {
                $errors[] = 'Instagram: '.$exception->getMessage();
            }
        }

        if (in_array('facebook', $channels, true)) {
            try {
                $results['facebook'] = $this->publishTemplateToFacebook($template);
            } catch (Throwable $exception) {
                $errors[] = 'Facebook: '.$exception->getMessage();
            }
        }

        if ($results === []) {
            throw new RuntimeException(implode(' | ', $errors));
        }

        if ($errors !== []) {
            $results['errors'] = $errors;
        }

        return $results;
    }

    public function publishTemplateByChannels(SocialMediaTemplate $template, array $channels): array
    {
        $selectedChannels = array_values(array_intersect($channels, ['instagram', 'facebook']));

        if ($selectedChannels === []) {
            throw new RuntimeException('Nenhum canal valido foi informado para publicacao.');
        }

        $template->forceFill([
            'publish_to_instagram' => in_array('instagram', $selectedChannels, true),
            'publish_to_facebook' => in_array('facebook', $selectedChannels, true),
        ]);

        return $this->publishTemplate($template);
    }

    public function publishTemplateToInstagram(SocialMediaTemplate $template): array
    {
        $template->loadMissing(['empresa', 'templateProducts.produto']);

        $integration = SocialMediaIntegration::query()
            ->where('empresa_id', $template->empresa_id)
            ->where('provider', 'instagram_graph')
            ->first();

        $integration = $this->assertIntegrationReady($integration, requireInstagram: true, requireFacebook: false);

        $imageUrl = $this->templateService->toAbsoluteImageUrl($this->templateService->resolveTemplateImageUrl($template));
        if ($imageUrl === '') {
            throw new RuntimeException('O template precisa de uma imagem principal para publicar no Instagram.');
        }

        $previewProducts = $template->templateProducts->map(function ($item): array {
            return [
                'name' => $item->custom_title ?: (string) ($item->produto?->NOME ?? 'Produto'),
                'price' => (float) ($item->produto?->PRECO ?? 0),
                'offer' => (float) ($item->produto?->OFERTA ?? 0),
                'show_price' => (bool) $item->show_price,
                'show_offer_price' => (bool) $item->show_offer_price,
            ];
        })->all();

        $caption = $this->templateService->buildCaptionPreview(
            (string) ($template->titulo ?? ''),
            (string) ($template->legenda ?? ''),
            $previewProducts,
        );

        try {
            $containerResponse = Http::asForm()->post($this->graphUrl('/'.$integration->instagram_business_account_id.'/media'), [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $integration->access_token,
            ])->throw()->json();

            $creationId = (string) Arr::get($containerResponse, 'id', '');
            if ($creationId === '') {
                throw new RuntimeException('Nao foi possivel criar o container de publicacao no Instagram.');
            }

            $publishResponse = Http::asForm()->post($this->graphUrl('/'.$integration->instagram_business_account_id.'/media_publish'), [
                'creation_id' => $creationId,
                'access_token' => $integration->access_token,
            ])->throw()->json();

            $publishId = (string) Arr::get($publishResponse, 'id', '');

            $template->update([
                'instagram_publish_status' => 'published',
                'instagram_publish_id' => $publishId !== '' ? $publishId : $creationId,
                'instagram_last_published_at' => now(),
                'instagram_last_error' => null,
            ]);

            $integration->update([
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            return [
                'publish_id' => $publishId !== '' ? $publishId : $creationId,
            ];
        } catch (Throwable $exception) {
            $message = $this->humanizeMetaException($exception);
            $template->update([
                'instagram_publish_status' => 'failed',
                'instagram_last_error' => $message,
            ]);

            $this->markIntegrationError($integration, $message, $this->isTokenProblem($exception) ? 'expired' : null);

            throw new RuntimeException($message);
        }
    }

    public function publishTemplateToFacebook(SocialMediaTemplate $template): array
    {
        $template->loadMissing(['empresa', 'templateProducts.produto']);

        $integration = SocialMediaIntegration::query()
            ->where('empresa_id', $template->empresa_id)
            ->where('provider', 'instagram_graph')
            ->first();

        $integration = $this->assertIntegrationReady($integration, requireInstagram: false, requireFacebook: true);

        $imageUrl = $this->templateService->toAbsoluteImageUrl($this->templateService->resolveTemplateImageUrl($template));
        if ($imageUrl === '') {
            throw new RuntimeException('O template precisa de uma imagem principal para publicar no Facebook.');
        }

        $previewProducts = $template->templateProducts->map(function ($item): array {
            return [
                'name' => $item->custom_title ?: (string) ($item->produto?->NOME ?? 'Produto'),
                'price' => (float) ($item->produto?->PRECO ?? 0),
                'offer' => (float) ($item->produto?->OFERTA ?? 0),
                'show_price' => (bool) $item->show_price,
                'show_offer_price' => (bool) $item->show_offer_price,
            ];
        })->all();

        $caption = $this->templateService->buildCaptionPreview(
            (string) ($template->titulo ?? ''),
            (string) ($template->legenda ?? ''),
            $previewProducts,
        );

        try {
            $response = Http::asForm()->post($this->graphUrl('/'.$integration->facebook_page_id.'/photos'), [
                'url' => $imageUrl,
                'caption' => $caption,
                'published' => 'true',
                'access_token' => $integration->access_token,
            ])->throw()->json();

            $publishId = (string) Arr::get($response, 'post_id', Arr::get($response, 'id', ''));
            if ($publishId === '') {
                throw new RuntimeException('Nao foi possivel criar a publicacao na pagina do Facebook.');
            }

            $template->update([
                'facebook_publish_status' => 'published',
                'facebook_publish_id' => $publishId,
                'facebook_last_published_at' => now(),
                'facebook_last_error' => null,
            ]);

            $integration->update([
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            return [
                'publish_id' => $publishId,
            ];
        } catch (Throwable $exception) {
            $message = $this->humanizeMetaException($exception);
            $template->update([
                'facebook_publish_status' => 'failed',
                'facebook_last_error' => $message,
            ]);

            $this->markIntegrationError($integration, $message, $this->isTokenProblem($exception) ? 'expired' : null);

            throw new RuntimeException($message);
        }
    }

    private function exchangeCode(string $code): array
    {
        return Http::get($this->graphUrl('/oauth/access_token'), [
            'client_id' => $this->appId(),
            'client_secret' => $this->appSecret(),
            'redirect_uri' => $this->redirectUri(),
            'code' => $code,
        ])->throw()->json();
    }

    private function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        if ($shortLivedToken === '') {
            throw new RuntimeException('Token temporario do Instagram nao encontrado.');
        }

        return Http::get($this->graphUrl('/oauth/access_token'), [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId(),
            'client_secret' => $this->appSecret(),
            'fb_exchange_token' => $shortLivedToken,
        ])->throw()->json();
    }

    private function resolveInstagramBusinessAccounts(string $userToken): array
    {
        $response = Http::get($this->graphUrl('/me/accounts'), [
            'fields' => 'id,name,access_token,instagram_business_account{id,username}',
            'access_token' => $userToken,
        ])->throw()->json('data', []);

        $pages = collect($response)
            ->filter(function ($item) {
                return filled(Arr::get($item, 'access_token'))
                    && filled(Arr::get($item, 'instagram_business_account.id'));
            })
            ->map(function ($page): array {
                return [
                    'facebook_page_id' => (string) Arr::get($page, 'id', ''),
                    'facebook_page_name' => (string) Arr::get($page, 'name', ''),
                    'instagram_business_account_id' => (string) Arr::get($page, 'instagram_business_account.id', ''),
                    'instagram_user_id' => (string) Arr::get($page, 'instagram_business_account.id', ''),
                    'instagram_username' => (string) Arr::get($page, 'instagram_business_account.username', ''),
                    'access_token' => (string) Arr::get($page, 'access_token', ''),
                ];
            })
            ->values()
            ->all();

        if ($pages === []) {
            throw new RuntimeException('Nenhuma pagina do Facebook com conta comercial do Instagram vinculada foi encontrada.');
        }

        return $pages;
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.$this->version().$path;
    }

    private function appId(): string
    {
        return trim((string) config('services.instagram_graph.app_id'));
    }

    private function appSecret(): string
    {
        return trim((string) config('services.instagram_graph.app_secret'));
    }

    private function redirectUri(): string
    {
        return trim((string) config('services.instagram_graph.redirect_uri'));
    }

    private function version(): string
    {
        return trim((string) config('services.instagram_graph.version', 'v22.0'));
    }

    private function resolveSelectedChannels(SocialMediaTemplate $template): array
    {
        $channels = [];

        $publishToInstagram = ! isset($template->publish_to_instagram) || (bool) $template->publish_to_instagram;
        $publishToFacebook = isset($template->publish_to_facebook) && (bool) $template->publish_to_facebook;

        if ($publishToInstagram) {
            $channels[] = 'instagram';
        }

        if ($publishToFacebook) {
            $channels[] = 'facebook';
        }

        return $channels;
    }

    private function assertIntegrationReady(?SocialMediaIntegration $integration, bool $requireInstagram, bool $requireFacebook): SocialMediaIntegration
    {
        if (! $integration || $integration->status !== 'connected' || ! $integration->access_token) {
            throw new RuntimeException('A empresa ainda nao possui uma integracao Meta conectada.');
        }

        $integration = $this->refreshIntegrationTokenIfNeeded($integration);

        if ($requireInstagram && ! $integration->instagram_business_account_id) {
            throw new RuntimeException('A integracao Meta nao possui uma conta comercial do Instagram vinculada.');
        }

        if ($requireFacebook && ! $integration->facebook_page_id) {
            throw new RuntimeException('A integracao Meta nao possui uma pagina do Facebook vinculada para publicar.');
        }

        return $integration;
    }

    private function refreshIntegrationTokenIfNeeded(SocialMediaIntegration $integration): SocialMediaIntegration
    {
        if (! $this->isExpired($integration)) {
            return $integration;
        }

        if (! $this->supportsMetaUserTokenPersistence() || ! filled($integration->meta_user_access_token)) {
            $this->markIntegrationError($integration, 'Token da Meta expirado. Reconecte a conta desta empresa para voltar a publicar.', 'expired');
            throw new RuntimeException('Token da Meta expirado. Reconecte a conta desta empresa para voltar a publicar.');
        }

        try {
            $refreshedUserToken = $this->exchangeForLongLivedToken((string) $integration->meta_user_access_token);
            $expiresIn = (int) Arr::get($refreshedUserToken, 'expires_in', 0);
            $metaUserAccessToken = (string) Arr::get($refreshedUserToken, 'access_token', '');
            $availableAccounts = $this->resolveInstagramBusinessAccounts($metaUserAccessToken);
            $selectedAccount = collect($availableAccounts)->firstWhere('facebook_page_id', (string) $integration->facebook_page_id);

            if (! $selectedAccount) {
                throw new RuntimeException('A pagina do Facebook vinculada nao foi encontrada na conta Meta renovada. Reconecte a integracao.');
            }

            $this->connectEmpresaWithSelection(
                $integration->empresa,
                $selectedAccount,
                $expiresIn,
                $metaUserAccessToken,
            );

            return $integration->refresh();
        } catch (Throwable $exception) {
            $message = $this->humanizeMetaException($exception);
            $this->markIntegrationError($integration, $message, 'expired');

            throw new RuntimeException($message);
        }
    }

    private function isExpired(SocialMediaIntegration $integration): bool
    {
        return $integration->access_token_expires_at !== null
            && $integration->access_token_expires_at->lessThanOrEqualTo(now());
    }

    private function resolveExpirationDate(int $expiresIn)
    {
        if ($expiresIn <= 0) {
            return null;
        }

        return now()->addSeconds($expiresIn);
    }

    private function supportsMetaUserTokenPersistence(): bool
    {
        return Schema::hasColumns('social_media_integrations', [
            'meta_user_access_token',
            'meta_user_access_token_expires_at',
        ]);
    }

    private function markIntegrationError(SocialMediaIntegration $integration, string $message, ?string $status = null): void
    {
        $payload = [
            'last_synced_at' => now(),
            'last_error' => $message,
        ];

        if ($status !== null) {
            $payload['status'] = $status;
        }

        $integration->update($payload);
    }

    private function humanizeMetaException(Throwable $exception): string
    {
        if ($exception instanceof RuntimeException) {
            return $exception->getMessage();
        }

        if ($exception instanceof RequestException) {
            $body = $exception->response?->json();
            $metaMessage = trim((string) Arr::get($body, 'error.message', ''));
            $metaCode = Arr::get($body, 'error.code');
            $metaSubcode = Arr::get($body, 'error.error_subcode');

            if ($this->isTokenProblem($exception)) {
                return 'Token da Meta expirado ou invalido. Reconecte a conta desta empresa e tente novamente.';
            }

            if ($metaMessage !== '') {
                $details = [];

                if ($metaCode !== null) {
                    $details[] = 'codigo '.$metaCode;
                }

                if ($metaSubcode !== null) {
                    $details[] = 'subcodigo '.$metaSubcode;
                }

                $suffix = $details === [] ? '' : ' ('.implode(', ', $details).')';

                return 'Meta retornou erro: '.$metaMessage.$suffix.'.';
            }
        }

        return 'Falha ao comunicar com a Meta. Tente novamente e, se persistir, reconecte a conta desta empresa.';
    }

    private function isTokenProblem(Throwable $exception): bool
    {
        if (! $exception instanceof RequestException) {
            return false;
        }

        $body = $exception->response?->json();
        $metaCode = (int) Arr::get($body, 'error.code', 0);
        $metaSubcode = (int) Arr::get($body, 'error.error_subcode', 0);
        $metaType = strtolower((string) Arr::get($body, 'error.type', ''));
        $metaMessage = strtolower((string) Arr::get($body, 'error.message', ''));

        return $metaCode === 190
            || in_array($metaSubcode, [458, 459, 460, 463, 464, 467], true)
            || str_contains($metaType, 'oauth')
            || str_contains($metaMessage, 'expired')
            || str_contains($metaMessage, 'invalid oauth')
            || str_contains($metaMessage, 'session has expired');
    }
}