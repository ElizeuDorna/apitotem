<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Produto;
use App\Models\SocialMediaIntegration;
use App\Models\SocialMediaTemplate;
use App\Models\SocialMediaTemplateProduct;
use App\Models\User;
use App\Support\EmpresaContext;
use App\Support\ImageStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SocialMediaTemplateService
{
    public function ensureAccess(User $user): int
    {
        $empresaId = EmpresaContext::resolveEmpresaIdForUser($user);

        if (! $empresaId) {
            abort(403, 'Selecione uma empresa ativa em Empresas para continuar.');
        }

        return (int) $empresaId;
    }

    public function activeEmpresaForUser(User $user): Empresa
    {
        $empresaId = $this->ensureAccess($user);

        return Empresa::query()->findOrFail($empresaId);
    }

    public function templatesQueryForUser(User $user): Builder
    {
        return SocialMediaTemplate::query()
            ->with(['templateProducts.produto'])
            ->where('empresa_id', $this->ensureAccess($user))
            ->orderByDesc('id');
    }

    public function availableProductsForUser(User $user): Collection
    {
        return Produto::query()
            ->where('empresa_id', $this->ensureAccess($user))
            ->orderBy('NOME')
            ->get();
    }

    public function integrationForUser(User $user): SocialMediaIntegration
    {
        return $this->integrationForEmpresaId($this->ensureAccess($user));
    }

    public function integrationForEmpresaId(int $empresaId): SocialMediaIntegration
    {
        return SocialMediaIntegration::query()->firstOrNew([
            'empresa_id' => $empresaId,
            'provider' => 'instagram_graph',
        ], [
            'status' => 'disconnected',
        ]);
    }

    public function authorizeTemplateAccess(User $user, SocialMediaTemplate $template): void
    {
        abort_unless((int) $template->empresa_id === $this->ensureAccess($user), 403);
    }

    public function saveForUser(User $user, array $data, ?SocialMediaTemplate $template = null): SocialMediaTemplate
    {
        $empresaId = $this->ensureAccess($user);

        if ($template) {
            $this->authorizeTemplateAccess($user, $template);
        }

        [$payload, $productsPayload] = $this->validateAndNormalize($empresaId, $data, $template);

        return DB::transaction(function () use ($empresaId, $template, $payload, $productsPayload) {
            $template ??= new SocialMediaTemplate();
            $template->fill($payload);
            $template->empresa_id = $empresaId;
            $template->save();

            $template->templateProducts()->delete();

            foreach ($productsPayload as $index => $item) {
                SocialMediaTemplateProduct::query()->create([
                    'social_media_template_id' => $template->id,
                    'produto_id' => $item['produto_id'],
                    'sort_order' => $index + 1,
                    'custom_title' => $item['custom_title'] ?: null,
                    'custom_image_url' => $item['custom_image_url'] ?: null,
                    'show_price' => $item['show_price'],
                    'show_offer_price' => $item['show_offer_price'],
                ]);
            }

            return $template->fresh(['templateProducts.produto']);
        });
    }

    public function deleteForUser(User $user, SocialMediaTemplate $template): void
    {
        $this->authorizeTemplateAccess($user, $template);
        $template->delete();
    }

    public function buildCaptionPreview(string $titulo, string $legenda, array $previewProducts): string
    {
        $parts = [];

        if (trim($titulo) !== '') {
            $parts[] = trim($titulo);
        }

        if (trim($legenda) !== '') {
            $parts[] = trim($legenda);
        }

        $productLines = collect($previewProducts)
            ->map(function (array $product): string {
                $line = '- '.trim((string) ($product['name'] ?? 'Produto'));

                if (($product['show_offer_price'] ?? false) && isset($product['offer']) && (float) $product['offer'] > 0) {
                    return $line.' | Oferta: R$ '.number_format((float) $product['offer'], 2, ',', '.');
                }

                if (($product['show_price'] ?? false) && isset($product['price']) && (float) $product['price'] > 0) {
                    return $line.' | Preco: R$ '.number_format((float) $product['price'], 2, ',', '.');
                }

                return $line;
            })
            ->filter()
            ->values()
            ->all();

        if ($productLines !== []) {
            $parts[] = implode(PHP_EOL, $productLines);
        }

        return trim(implode(PHP_EOL.PHP_EOL, $parts));
    }

    public function resolveTemplateImageUrl(SocialMediaTemplate $template): string
    {
        $template->loadMissing('templateProducts.produto');

        $coverImageUrl = ImageStorage::normalizePublicUrl((string) ($template->cover_image_url ?? ''));
        if ($coverImageUrl !== '') {
            return $coverImageUrl;
        }

        $firstItem = $template->templateProducts->first();
        if (! $firstItem) {
            return '';
        }

        $customImageUrl = ImageStorage::normalizePublicUrl((string) ($firstItem->custom_image_url ?? ''));
        if ($customImageUrl !== '') {
            return $customImageUrl;
        }

        return ImageStorage::normalizePublicUrl((string) ($firstItem->produto?->IMG ?? ''));
    }

    public function toAbsoluteImageUrl(string $url): string
    {
        $normalized = ImageStorage::normalizePublicUrl($url);

        if ($normalized === '') {
            return '';
        }

        if (ImageStorage::isInternalPublicPath($normalized)) {
            $path = str_starts_with($normalized, '/') ? $normalized : '/'.$normalized;

            return url($path);
        }

        return $normalized;
    }

    private function validateAndNormalize(int $empresaId, array $data, ?SocialMediaTemplate $template = null): array
    {
        $validator = validator($data, [
            'nome' => ['required', 'string', 'max:120'],
            'titulo' => ['nullable', 'string', 'max:160'],
            'legenda' => ['nullable', 'string', 'max:5000'],
            'layout_mode' => ['required', Rule::in(['image', 'product_list', 'mixed'])],
            'cover_image_url' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                if (! ImageStorage::isValidImagePathOrUrl((string) $value)) {
                    $fail('Informe uma URL valida ou um caminho interno iniciando com /storage/ ou /storage-images/.');
                }
            }],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date', 'after_or_equal:scheduled_start_at'],
            'instagram_auto_publish' => ['nullable', 'boolean'],
            'facebook_auto_publish' => ['nullable', 'boolean'],
            'publish_to_instagram' => ['nullable', 'boolean'],
            'publish_to_facebook' => ['nullable', 'boolean'],
            'selected_products' => ['array'],
            'selected_products.*.produto_id' => ['required', 'integer', 'exists:produto,id'],
            'selected_products.*.custom_title' => ['nullable', 'string', 'max:160'],
            'selected_products.*.custom_image_url' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                if (! ImageStorage::isValidImagePathOrUrl((string) $value)) {
                    $fail('Informe uma URL valida ou um caminho interno iniciando com /storage/ ou /storage-images/.');
                }
            }],
            'selected_products.*.show_price' => ['nullable', 'boolean'],
            'selected_products.*.show_offer_price' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($data) {
            $layoutMode = (string) ($data['layout_mode'] ?? 'product_list');
            $selectedProducts = collect($data['selected_products'] ?? [])->filter(fn ($item) => ! empty($item['produto_id']));
            $publishToInstagram = (bool) ($data['publish_to_instagram'] ?? true);
            $publishToFacebook = (bool) ($data['publish_to_facebook'] ?? false);

            if ($layoutMode !== 'image' && $selectedProducts->isEmpty()) {
                $validator->errors()->add('selected_products', 'Selecione pelo menos um produto para o template.');
            }

            if (! $publishToInstagram && ! $publishToFacebook) {
                $validator->errors()->add('publish_channels', 'Selecione pelo menos uma rede social para divulgar este template.');
            }
        });

        $validated = $validator->validate();

        $productItems = collect($validated['selected_products'] ?? [])
            ->map(function (array $item): array {
                return [
                    'produto_id' => (int) $item['produto_id'],
                    'custom_title' => trim((string) ($item['custom_title'] ?? '')),
                    'custom_image_url' => ImageStorage::normalizePublicUrl((string) ($item['custom_image_url'] ?? '')),
                    'show_price' => (bool) ($item['show_price'] ?? false),
                    'show_offer_price' => (bool) ($item['show_offer_price'] ?? false),
                ];
            })
            ->unique('produto_id')
            ->values();

        $products = Produto::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('id', $productItems->pluck('produto_id'))
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productItems->count()) {
            throw ValidationException::withMessages([
                'selected_products' => 'Existe produto selecionado fora da empresa ativa.',
            ]);
        }

        $coverImageUrl = ImageStorage::normalizePublicUrl((string) ($validated['cover_image_url'] ?? ''));

        if ($coverImageUrl === '') {
            $firstProduct = $productItems->first();
            if ($firstProduct) {
                $coverImageUrl = $firstProduct['custom_image_url'];

                if ($coverImageUrl === '') {
                    $coverImageUrl = ImageStorage::normalizePublicUrl((string) ($products->get($firstProduct['produto_id'])?->IMG ?? ''));
                }
            }
        }

        $publishToInstagram = (bool) ($validated['publish_to_instagram'] ?? true);
        $publishToFacebook = (bool) ($validated['publish_to_facebook'] ?? false);
        $instagramAutoPublish = (bool) ($validated['instagram_auto_publish'] ?? false);
        $facebookAutoPublish = (bool) ($validated['facebook_auto_publish'] ?? false);

        $instagramStatus = $this->resolveChannelStatus(
            enabled: $publishToInstagram,
            autoPublish: $instagramAutoPublish,
            scheduledStartAt: $validated['scheduled_start_at'] ?? null,
            scheduledEndAt: $validated['scheduled_end_at'] ?? null,
            previousStatus: (string) ($template?->instagram_publish_status ?? 'draft'),
        );

        $facebookStatus = $this->resolveChannelStatus(
            enabled: $publishToFacebook,
            autoPublish: $facebookAutoPublish,
            scheduledStartAt: $validated['scheduled_start_at'] ?? null,
            scheduledEndAt: $validated['scheduled_end_at'] ?? null,
            previousStatus: (string) ($template?->facebook_publish_status ?? 'draft'),
        );

        $payload = [
            'nome' => trim((string) $validated['nome']),
            'titulo' => trim((string) ($validated['titulo'] ?? '')) ?: null,
            'legenda' => trim((string) ($validated['legenda'] ?? '')) ?: null,
            'layout_mode' => $validated['layout_mode'],
            'cover_image_url' => $coverImageUrl ?: null,
            'scheduled_start_at' => $validated['scheduled_start_at'] ?? null,
            'scheduled_end_at' => $validated['scheduled_end_at'] ?? null,
            'instagram_auto_publish' => $instagramAutoPublish,
            'facebook_auto_publish' => $facebookAutoPublish,
            'publish_to_instagram' => $publishToInstagram,
            'publish_to_facebook' => $publishToFacebook,
            'instagram_publish_status' => $instagramStatus,
            'facebook_publish_status' => $facebookStatus,
            'instagram_last_error' => $instagramStatus === 'published' ? ($template?->instagram_last_error) : null,
            'facebook_last_error' => $facebookStatus === 'published' ? ($template?->facebook_last_error) : null,
        ];

        if (! Schema::hasColumn('social_media_templates', 'facebook_auto_publish')) {
            unset(
                $payload['facebook_auto_publish'],
                $payload['publish_to_instagram'],
                $payload['publish_to_facebook'],
                $payload['facebook_publish_status'],
                $payload['facebook_last_error'],
            );
        }

        return [$payload, $productItems->all()];
    }

    private function resolveChannelStatus(bool $enabled, bool $autoPublish, mixed $scheduledStartAt, mixed $scheduledEndAt, string $previousStatus): string
    {
        if (! $enabled) {
            return 'disabled';
        }

        if ($scheduledEndAt && now()->greaterThan($scheduledEndAt)) {
            return 'expired';
        }

        if ($previousStatus === 'published') {
            return 'published';
        }

        if ($autoPublish && $scheduledStartAt) {
            return 'scheduled';
        }

        return 'draft';
    }
}