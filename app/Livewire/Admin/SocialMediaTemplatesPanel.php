<?php

namespace App\Livewire\Admin;

use App\Models\SocialMediaTemplate;
use App\Services\InstagramGraphService;
use App\Services\SocialMediaTemplateService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SocialMediaTemplatesPanel extends Component
{
    public ?int $editingTemplateId = null;

    public string $nome = '';

    public string $titulo = '';

    public string $legenda = '';

    public string $layoutMode = 'product_list';

    public string $coverImageUrl = '';

    public string $imagePublishMode = 'single';

    public string $scheduledStartAt = '';

    public string $scheduledEndAt = '';

    public bool $instagramAutoPublish = false;

    public bool $facebookAutoPublish = false;

    public bool $publishToInstagram = true;

    public bool $publishToFacebook = false;

    public string $productToAdd = '';

    public string $productSearch = '';

    public array $selectedProducts = [];

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function addProduct(SocialMediaTemplateService $templateService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $productId = (int) $this->productToAdd;
        if ($productId <= 0) {
            $this->errorMessage = 'Selecione um produto para adicionar ao template.';
            return;
        }

        foreach ($this->selectedProducts as $item) {
            if ((int) ($item['produto_id'] ?? 0) === $productId) {
                $this->errorMessage = 'Este produto ja esta no template.';
                return;
            }
        }

        $product = $templateService->availableProductsForUser($user)->firstWhere('id', $productId);
        if (! $product) {
            $this->errorMessage = 'Produto nao encontrado para a empresa ativa.';
            return;
        }

        $this->selectedProducts[] = [
            'produto_id' => (string) $product->id,
            'nome' => (string) $product->NOME,
            'codigo' => (string) $product->CODIGO,
            'default_image_url' => (string) ($product->IMG ?? ''),
            'custom_title' => '',
            'custom_image_url' => '',
            'show_price' => true,
            'show_offer_price' => true,
        ];

        if ($this->coverImageUrl === '') {
            $this->coverImageUrl = (string) ($product->IMG ?? '');
        }

        $this->productToAdd = '';
        $this->productSearch = '';
        $this->errorMessage = null;
    }

    public function selectProduct(int $productId, SocialMediaTemplateService $templateService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $product = $templateService->availableProductsForUser($user)->firstWhere('id', $productId);
        if (! $product) {
            $this->errorMessage = 'Produto nao encontrado para a empresa ativa.';
            return;
        }

        $this->productToAdd = (string) $product->id;
        $this->productSearch = trim((string) $product->NOME.' '.($product->CODIGO ? '| '.$product->CODIGO : ''));
        $this->errorMessage = null;
    }

    public function removeProduct(int $index): void
    {
        if (! isset($this->selectedProducts[$index])) {
            return;
        }

        unset($this->selectedProducts[$index]);
        $this->selectedProducts = array_values($this->selectedProducts);
    }

    public function moveProductUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->selectedProducts[$index], $this->selectedProducts[$index - 1])) {
            return;
        }

        [$this->selectedProducts[$index - 1], $this->selectedProducts[$index]] = [$this->selectedProducts[$index], $this->selectedProducts[$index - 1]];
        $this->selectedProducts = array_values($this->selectedProducts);
    }

    public function moveProductDown(int $index): void
    {
        if (! isset($this->selectedProducts[$index], $this->selectedProducts[$index + 1])) {
            return;
        }

        [$this->selectedProducts[$index + 1], $this->selectedProducts[$index]] = [$this->selectedProducts[$index], $this->selectedProducts[$index + 1]];
        $this->selectedProducts = array_values($this->selectedProducts);
    }

    public function editTemplate(int $templateId, SocialMediaTemplateService $templateService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $template = $templateService->templatesQueryForUser($user)->findOrFail($templateId);
        $templateService->authorizeTemplateAccess($user, $template);

        $this->editingTemplateId = $template->id;
        $this->nome = (string) ($template->nome ?? '');
        $this->titulo = (string) ($template->titulo ?? '');
        $this->legenda = (string) ($template->legenda ?? '');
        $this->layoutMode = (string) ($template->layout_mode ?? 'product_list');
        $this->coverImageUrl = (string) ($template->cover_image_url ?? '');
        $this->imagePublishMode = (string) ($template->image_publish_mode ?? 'single');
        $this->scheduledStartAt = optional($template->scheduled_start_at)->format('Y-m-d\TH:i') ?? '';
        $this->scheduledEndAt = optional($template->scheduled_end_at)->format('Y-m-d\TH:i') ?? '';
        $this->instagramAutoPublish = (bool) $template->instagram_auto_publish;
        $this->facebookAutoPublish = (bool) ($template->facebook_auto_publish ?? false);
        $this->publishToInstagram = ! isset($template->publish_to_instagram) || (bool) $template->publish_to_instagram;
        $this->publishToFacebook = (bool) ($template->publish_to_facebook ?? false);
        $this->selectedProducts = $template->templateProducts->map(function ($item): array {
            return [
                'produto_id' => (string) $item->produto_id,
                'nome' => (string) ($item->produto?->NOME ?? 'Produto'),
                'codigo' => (string) ($item->produto?->CODIGO ?? ''),
                'default_image_url' => (string) ($item->produto?->IMG ?? ''),
                'custom_title' => (string) ($item->custom_title ?? ''),
                'custom_image_url' => (string) ($item->custom_image_url ?? ''),
                'show_price' => (bool) $item->show_price,
                'show_offer_price' => (bool) $item->show_offer_price,
            ];
        })->all();

        $this->statusMessage = 'Template carregado para edicao.';
        $this->errorMessage = null;
    }

    public function save(SocialMediaTemplateService $templateService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $template = $this->editingTemplateId
            ? $templateService->templatesQueryForUser($user)->findOrFail($this->editingTemplateId)
            : null;

        $saved = $templateService->saveForUser($user, [
            'nome' => $this->nome,
            'titulo' => $this->titulo,
            'legenda' => $this->legenda,
            'layout_mode' => $this->layoutMode,
            'cover_image_url' => $this->coverImageUrl,
            'image_publish_mode' => $this->imagePublishMode,
            'scheduled_start_at' => $this->scheduledStartAt !== '' ? $this->scheduledStartAt : null,
            'scheduled_end_at' => $this->scheduledEndAt !== '' ? $this->scheduledEndAt : null,
            'instagram_auto_publish' => $this->instagramAutoPublish,
            'facebook_auto_publish' => $this->facebookAutoPublish,
            'publish_to_instagram' => $this->publishToInstagram,
            'publish_to_facebook' => $this->publishToFacebook,
            'selected_products' => $this->selectedProducts,
        ], $template);

        $this->resetForm();
        $this->statusMessage = $template ? 'Template atualizado com sucesso.' : 'Template criado com sucesso.';
        $this->errorMessage = null;
        $this->editingTemplateId = null;

        if ($saved->instagram_publish_status === 'published') {
            $this->statusMessage .= ' O historico de publicacao foi preservado.';
        }
    }

    public function deleteTemplate(int $templateId, SocialMediaTemplateService $templateService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $template = $templateService->templatesQueryForUser($user)->findOrFail($templateId);
        $templateService->deleteForUser($user, $template);

        if ($this->editingTemplateId === $templateId) {
            $this->resetForm();
        }

        $this->statusMessage = 'Template removido com sucesso.';
        $this->errorMessage = null;
    }

    public function publishNow(int $templateId, SocialMediaTemplateService $templateService, InstagramGraphService $instagramService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $template = $templateService->templatesQueryForUser($user)->findOrFail($templateId);

        try {
            $result = $instagramService->publishTemplate($template);
            $channels = array_map('strtolower', array_keys(array_intersect_key($result, array_flip(['instagram', 'facebook']))));
            $this->statusMessage = 'Template publicado com sucesso em '.implode(' e ', $channels).'.';

            if (! empty($result['errors'])) {
                $this->statusMessage .= ' Com alerta: '.implode(' | ', $result['errors']);
            }

            $this->errorMessage = null;
        } catch (\Throwable $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function testIntegration(SocialMediaTemplateService $templateService, InstagramGraphService $instagramService): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $integration = $templateService->integrationForUser($user);
        $integrationCanAutoRefresh = $instagramService->canAttemptAutomaticRefresh($integration);
        $integrationReady = $integration->status === 'connected' || $integrationCanAutoRefresh;

        try {
            $result = $instagramService->testIntegration($integration);
            $this->statusMessage = 'Conexao Meta validada com sucesso para Instagram @'.($result['instagram_username'] ?: 'instagram').' e pagina '.($result['facebook_page_name'] ?: 'Facebook').'.';
            $this->errorMessage = null;
        } catch (\Throwable $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingTemplateId',
            'nome',
            'titulo',
            'legenda',
            'layoutMode',
            'coverImageUrl',
            'imagePublishMode',
            'scheduledStartAt',
            'scheduledEndAt',
            'instagramAutoPublish',
            'facebookAutoPublish',
            'publishToInstagram',
            'publishToFacebook',
            'productToAdd',
            'productSearch',
            'selectedProducts',
        ]);

        $this->layoutMode = 'product_list';
        $this->imagePublishMode = 'single';
        $this->instagramAutoPublish = false;
        $this->facebookAutoPublish = false;
        $this->publishToInstagram = true;
        $this->publishToFacebook = false;
        $this->selectedProducts = [];
    }

    public function render(SocialMediaTemplateService $templateService, InstagramGraphService $instagramService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $templates = $templateService->templatesQueryForUser($user)->get();
        $availableProducts = $templateService->availableProductsForUser($user);
        $productsById = $availableProducts->keyBy('id');
        $selectedProductIds = collect($this->selectedProducts)
            ->pluck('produto_id')
            ->map(fn ($productId) => (int) $productId)
            ->all();
        $searchTerm = trim($this->productSearch);
        $filteredProducts = $availableProducts
            ->filter(function ($product) use ($searchTerm, $selectedProductIds): bool {
                if (in_array((int) $product->id, $selectedProductIds, true)) {
                    return false;
                }

                if ($searchTerm === '') {
                    return true;
                }

                $haystack = mb_strtolower(trim((string) $product->NOME.' '.(string) $product->CODIGO));
                return str_contains($haystack, mb_strtolower($searchTerm));
            })
            ->take($searchTerm === '' ? 40 : 80)
            ->values();
        $selectedProductOption = $this->productToAdd !== ''
            ? $availableProducts->firstWhere('id', (int) $this->productToAdd)
            : null;
        $integration = $templateService->integrationForUser($user);
        $integrationCanAutoRefresh = $instagramService->canAttemptAutomaticRefresh($integration);
        $integrationReady = $integration->status === 'connected' || $integrationCanAutoRefresh;

        $previewProducts = collect($this->selectedProducts)
            ->map(function (array $item) use ($productsById): array {
                $product = $productsById->get((int) ($item['produto_id'] ?? 0));

                return [
                    'name' => trim((string) ($item['custom_title'] ?? '')) !== ''
                        ? trim((string) ($item['custom_title'] ?? ''))
                        : (string) ($product->NOME ?? $item['nome'] ?? 'Produto'),
                    'code' => (string) ($product->CODIGO ?? $item['codigo'] ?? ''),
                    'price' => (float) ($product->PRECO ?? 0),
                    'offer' => (float) ($product->OFERTA ?? 0),
                    'image_url' => trim((string) ($item['custom_image_url'] ?? '')) !== ''
                        ? (string) $item['custom_image_url']
                        : (string) ($item['default_image_url'] ?? $product->IMG ?? ''),
                    'show_price' => (bool) ($item['show_price'] ?? false),
                    'show_offer_price' => (bool) ($item['show_offer_price'] ?? false),
                ];
            })
            ->filter(fn (array $item) => trim((string) ($item['name'] ?? '')) !== '')
            ->values()
            ->all();

        return view('livewire.admin.social-media-templates-panel', [
            'templates' => $templates,
            'availableProducts' => $availableProducts,
            'filteredProducts' => $filteredProducts,
            'selectedProductOption' => $selectedProductOption,
            'previewProducts' => $previewProducts,
            'previewCaption' => $templateService->buildCaptionPreview($this->titulo, $this->legenda, $previewProducts),
            'previewImageUrl' => $this->coverImageUrl !== ''
                ? $this->coverImageUrl
                : (string) ($previewProducts[0]['image_url'] ?? ''),
            'previewImageModeLabel' => $this->imagePublishMode === 'product_images' ? 'Todas as imagens dos produtos' : 'Imagem unica',
            'galleryPickerUrl' => route('admin.galeria-imagem.index', ['abrir_form' => 1, 'selecionar_social_media' => 1]),
            'instagramConfigured' => $instagramService->isConfigured(),
            'integration' => $integration,
            'integrationReady' => $integrationReady,
            'integrationCanAutoRefresh' => $integrationCanAutoRefresh,
            'integrationStatus' => $instagramService->integrationStatusSummary($integration),
        ]);
    }
}