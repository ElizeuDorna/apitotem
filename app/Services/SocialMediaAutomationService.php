<?php

namespace App\Services;

use App\Models\Produto;
use App\Models\SocialMediaAutomationPublication;
use App\Models\SocialMediaAutomationSetting;
use App\Models\SocialMediaTemplate;
use App\Models\SocialMediaTemplateProduct;
use App\Models\User;
use App\Support\ImageStorage;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SocialMediaAutomationService
{
    public function settingsForUser(User $user, SocialMediaTemplateService $templateService): SocialMediaAutomationSetting
    {
        return $this->settingsForEmpresaId($templateService->ensureAccess($user));
    }

    public function settingsForEmpresaId(int $empresaId): SocialMediaAutomationSetting
    {
        if (! Schema::hasTable('social_media_automation_settings')) {
            return new SocialMediaAutomationSetting([
                'empresa_id' => $empresaId,
                'enabled' => false,
                'mode' => 'daily_offers',
                'publish_to_instagram' => true,
                'publish_to_facebook' => false,
                'publish_times' => ['09:00'],
                'max_products_per_post' => 10,
                'require_image' => true,
                'republish_after_hours' => 24,
                'title_prefix' => 'Ofertas do dia',
                'caption_prefix' => 'Confira as ofertas selecionadas de hoje.',
            ]);
        }

        return SocialMediaAutomationSetting::query()->firstOrNew([
            'empresa_id' => $empresaId,
        ], [
            'enabled' => false,
            'mode' => 'daily_offers',
            'publish_to_instagram' => true,
            'publish_to_facebook' => false,
            'publish_times' => ['09:00'],
            'max_products_per_post' => 10,
            'require_image' => true,
            'republish_after_hours' => 24,
            'title_prefix' => 'Ofertas do dia',
            'caption_prefix' => 'Confira as ofertas selecionadas de hoje.',
        ]);
    }

    public function recentPublicationsForEmpresaId(int $empresaId, int $limit = 8): Collection
    {
        if (! Schema::hasTable('social_media_automation_publications')) {
            return collect();
        }

        return SocialMediaAutomationPublication::query()
            ->with(['produto', 'template'])
            ->where('empresa_id', $empresaId)
            ->latest('published_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function saveSettingsForUser(User $user, SocialMediaTemplateService $templateService, array $data): SocialMediaAutomationSetting
    {
        if (! Schema::hasTable('social_media_automation_settings')) {
            throw ValidationException::withMessages([
                'automation' => 'Rode as migrations da automacao social antes de salvar esta configuracao.',
            ]);
        }

        $empresaId = $templateService->ensureAccess($user);
        $settings = $this->settingsForEmpresaId($empresaId);

        $validator = validator($data, [
            'enabled' => ['nullable', 'boolean'],
            'mode' => ['required', Rule::in(['individual_offer', 'daily_offers'])],
            'publish_to_instagram' => ['nullable', 'boolean'],
            'publish_to_facebook' => ['nullable', 'boolean'],
            'publish_times' => ['array'],
            'publish_times.*' => ['nullable', 'date_format:H:i'],
            'max_products_per_post' => ['required', 'integer', 'min:1', 'max:30'],
            'require_image' => ['nullable', 'boolean'],
            'republish_after_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'title_prefix' => ['nullable', 'string', 'max:160'],
            'caption_prefix' => ['nullable', 'string', 'max:3000'],
        ]);

        $validator->after(function ($validator) use ($data): void {
            $enabled = (bool) ($data['enabled'] ?? false);
            $publishTimes = collect($data['publish_times'] ?? [])
                ->map(fn ($time) => trim((string) $time))
                ->filter()
                ->unique()
                ->values();

            if (! (bool) ($data['publish_to_instagram'] ?? false) && ! (bool) ($data['publish_to_facebook'] ?? false)) {
                $validator->errors()->add('automation_channels', 'Selecione pelo menos um canal para a automacao.');
            }

            if ($enabled && $publishTimes->isEmpty()) {
                $validator->errors()->add('publish_times', 'Informe pelo menos um horario para disparo automatico.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $publishTimes = collect($validated['publish_times'] ?? [])
            ->map(fn ($time) => trim((string) $time))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $settings->fill([
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'mode' => (string) $validated['mode'],
            'publish_to_instagram' => (bool) ($validated['publish_to_instagram'] ?? false),
            'publish_to_facebook' => (bool) ($validated['publish_to_facebook'] ?? false),
            'publish_times' => $publishTimes,
            'max_products_per_post' => (int) $validated['max_products_per_post'],
            'require_image' => (bool) ($validated['require_image'] ?? false),
            'republish_after_hours' => (int) $validated['republish_after_hours'],
            'title_prefix' => trim((string) ($validated['title_prefix'] ?? '')) ?: null,
            'caption_prefix' => trim((string) ($validated['caption_prefix'] ?? '')) ?: null,
        ]);
        $settings->empresa_id = $empresaId;
        $settings->save();

        return $settings;
    }

    public function dispatchDueAutomations(?CarbonInterface $now = null): array
    {
        if (! Schema::hasTable('social_media_automation_settings') || ! Schema::hasTable('social_media_automation_publications')) {
            return [
                'processed' => 0,
                'published' => 0,
            ];
        }

        $now ??= now();
        $processed = 0;
        $published = 0;

        $settingsCollection = SocialMediaAutomationSetting::query()
            ->where('enabled', true)
            ->get();

        foreach ($settingsCollection as $settings) {
            if (! $this->isDueAt($settings, $now)) {
                continue;
            }

            $processed++;
            $published += $this->dispatchForSettings($settings, $now);
        }

        return [
            'processed' => $processed,
            'published' => $published,
        ];
    }

    public function isDueAt(SocialMediaAutomationSetting $settings, CarbonInterface $now): bool
    {
        if (! $settings->enabled) {
            return false;
        }

        $times = collect($settings->publish_times ?? [])
            ->map(fn ($time) => trim((string) $time))
            ->filter();

        return $times->contains($now->format('H:i'));
    }

    private function dispatchForSettings(SocialMediaAutomationSetting $settings, CarbonInterface $now): int
    {
        $templateService = app(SocialMediaTemplateService::class);
        $instagramService = app(InstagramGraphService::class);
        $integration = $templateService->integrationForEmpresaId((int) $settings->empresa_id);

        if (! $instagramService->canAttemptAutomaticRefresh($integration) && $integration->status !== 'connected') {
            return 0;
        }

        $products = $this->eligibleOfferProducts($settings, $now);
        if ($products->isEmpty()) {
            return 0;
        }

        if ($settings->mode === 'individual_offer') {
            return $products->take($settings->max_products_per_post)->sum(function (Produto $product) use ($settings, $now, $instagramService): int {
                return $this->publishProductsBatch($settings, collect([$product]), $now, $instagramService, true) ? 1 : 0;
            });
        }

        return $this->publishProductsBatch($settings, $products->take($settings->max_products_per_post), $now, $instagramService, false) ? (int) min($products->count(), $settings->max_products_per_post) : 0;
    }

    private function eligibleOfferProducts(SocialMediaAutomationSetting $settings, CarbonInterface $now): Collection
    {
        $products = Produto::query()
            ->where('empresa_id', $settings->empresa_id)
            ->whereNotNull('OFERTA')
            ->where('OFERTA', '>', 0)
            ->get()
            ->filter(function (Produto $product) use ($settings): bool {
                if ((float) $product->OFERTA <= 0) {
                    return false;
                }

                if (! $settings->require_image) {
                    return true;
                }

                return ImageStorage::normalizePublicUrl((string) ($product->IMG ?? '')) !== '';
            })
            ->sortByDesc(fn (Produto $product) => max((float) $product->PRECO - (float) $product->OFERTA, 0))
            ->values();

        if ($products->isEmpty()) {
            return collect();
        }

        $cutoff = $now->copy()->subHours(max((int) $settings->republish_after_hours, 1));
        $dedupeKeys = $products->map(fn (Produto $product) => $this->dedupeKeyForProduct($product, (string) $settings->mode))->all();
        $publishedKeys = SocialMediaAutomationPublication::query()
            ->where('empresa_id', $settings->empresa_id)
            ->where('mode', $settings->mode)
            ->where('status', 'published')
            ->where('published_at', '>=', $cutoff)
            ->whereIn('dedupe_key', $dedupeKeys)
            ->pluck('dedupe_key')
            ->all();

        return $products
            ->reject(fn (Produto $product) => in_array($this->dedupeKeyForProduct($product, (string) $settings->mode), $publishedKeys, true))
            ->values();
    }

    private function publishProductsBatch(SocialMediaAutomationSetting $settings, Collection $products, CarbonInterface $now, InstagramGraphService $instagramService, bool $individualMode): bool
    {
        if ($products->isEmpty()) {
            return false;
        }

        $batchKey = implode(':', [
            $settings->mode,
            $settings->empresa_id,
            $now->format('YmdHi'),
            $individualMode ? 'individual' : 'grouped',
            $products->pluck('id')->implode('-'),
        ]);

        $template = $this->createAutomationTemplate($settings, $products, $batchKey, $individualMode);
        $channels = [];

        if ($settings->publish_to_instagram) {
            $channels[] = 'instagram';
        }

        if ($settings->publish_to_facebook) {
            $channels[] = 'facebook';
        }

        if ($channels === []) {
            return false;
        }

        try {
            $instagramService->publishTemplateByChannels($template, $channels);

            foreach ($products as $product) {
                SocialMediaAutomationPublication::query()->updateOrCreate([
                    'empresa_id' => $settings->empresa_id,
                    'mode' => $settings->mode,
                    'dedupe_key' => $this->dedupeKeyForProduct($product, (string) $settings->mode),
                ], [
                    'social_media_automation_setting_id' => $settings->id,
                    'social_media_template_id' => $template->id,
                    'produto_id' => $product->id,
                    'status' => 'published',
                    'batch_key' => $batchKey,
                    'error_message' => null,
                    'published_at' => $now,
                ]);
            }

            return true;
        } catch (\Throwable $exception) {
            foreach ($products as $product) {
                SocialMediaAutomationPublication::query()->updateOrCreate([
                    'empresa_id' => $settings->empresa_id,
                    'mode' => $settings->mode,
                    'dedupe_key' => $this->dedupeKeyForProduct($product, (string) $settings->mode),
                ], [
                    'social_media_automation_setting_id' => $settings->id,
                    'social_media_template_id' => $template->id,
                    'produto_id' => $product->id,
                    'status' => 'failed',
                    'batch_key' => $batchKey,
                    'error_message' => $exception->getMessage(),
                    'published_at' => null,
                ]);
            }

            return false;
        }
    }

    private function createAutomationTemplate(SocialMediaAutomationSetting $settings, Collection $products, string $batchKey, bool $individualMode): SocialMediaTemplate
    {
        return DB::transaction(function () use ($settings, $products, $batchKey, $individualMode): SocialMediaTemplate {
            $titlePrefix = trim((string) ($settings->title_prefix ?? ''));
            $captionPrefix = trim((string) ($settings->caption_prefix ?? ''));
            $defaultTitle = $individualMode ? 'Oferta automatica' : 'Ofertas do dia';
            $title = $titlePrefix !== '' ? $titlePrefix : $defaultTitle;

            $templatePayload = [
                'empresa_id' => $settings->empresa_id,
                'nome' => '[Auto] '.$title.' '.$batchKey,
                'titulo' => $title,
                'legenda' => $captionPrefix,
                'layout_mode' => $products->count() > 1 ? 'product_list' : 'mixed',
                'cover_image_url' => (string) ($products->first()?->IMG ?? ''),
                'image_publish_mode' => $products->count() > 1 ? 'product_images' : 'single',
                'scheduled_start_at' => null,
                'scheduled_end_at' => null,
                'instagram_auto_publish' => false,
                'facebook_auto_publish' => false,
                'publish_to_instagram' => (bool) $settings->publish_to_instagram,
                'publish_to_facebook' => (bool) $settings->publish_to_facebook,
                'instagram_publish_status' => 'draft',
                'facebook_publish_status' => 'draft',
            ];

            if (Schema::hasColumn('social_media_templates', 'source_type')) {
                $templatePayload['source_type'] = 'automation';
            }

            if (Schema::hasColumn('social_media_templates', 'automation_batch_key')) {
                $templatePayload['automation_batch_key'] = $batchKey;
            }

            $template = SocialMediaTemplate::query()->create($templatePayload);

            foreach ($products->values() as $index => $product) {
                SocialMediaTemplateProduct::query()->create([
                    'social_media_template_id' => $template->id,
                    'produto_id' => $product->id,
                    'sort_order' => $index + 1,
                    'custom_title' => null,
                    'custom_image_url' => null,
                    'show_price' => true,
                    'show_offer_price' => true,
                ]);
            }

            return $template->fresh(['templateProducts.produto']);
        });
    }

    private function dedupeKeyForProduct(Produto $product, string $mode): string
    {
        return sha1(implode('|', [
            $mode,
            (int) $product->id,
            number_format((float) $product->PRECO, 2, '.', ''),
            number_format((float) $product->OFERTA, 2, '.', ''),
            ImageStorage::normalizePublicUrl((string) ($product->IMG ?? '')),
        ]));
    }
}
