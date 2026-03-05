<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\GlobalImageGallery;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class WebScreenConfigController extends Controller
{
    public function edit(): View
    {
        $empresaId = $this->resolveEmpresaId();

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $config = $config->fresh();
        $config->rightSidebarImageUrls = $this->normalizeImageUrlsList((string) ($config->rightSidebarImageUrls ?? ''));

        return view('admin.web-screen-config', [
            'config' => $config,
            'companyGalleryImages' => $this->listCompanyGalleryImages(),
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $empresaId = $this->resolveEmpresaId();
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['items' => []]);
        }

        $items = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('CODIGO', 'like', $query.'%')
                    ->orWhere('NOME', 'like', '%'.$query.'%');
            })
            ->orderBy('NOME')
            ->limit(20)
            ->get(['CODIGO', 'NOME'])
            ->map(fn (Produto $produto) => [
                'codigo' => (string) $produto->CODIGO,
                'nome' => (string) $produto->NOME,
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    public function update(Request $request): RedirectResponse
    {
        $empresaId = $this->resolveEmpresaId();

        $rawVideoUrls = collect($request->input('video_urls', []))
            ->map(fn ($value) => $this->extractVideoUrlFromInput((string) $value))
            ;

        $rawMutedFlags = collect($request->input('video_muted_flags', []))
            ->map(fn ($value) => (string) $value === '1')
            ;

        $rawActiveFlags = collect($request->input('video_active_flags', []))
            ->map(fn ($value) => (string) $value === '1')
            ;

        $rawFullscreenFlags = collect($request->input('video_fullscreen_flags', []))
            ->map(fn ($value) => (string) $value === '1')
            ;

        $rawDurationSeconds = collect($request->input('video_duration_seconds', []))
            ->map(function ($value) {
                if ($value === null || $value === '') {
                    return 0;
                }

                return max(0, (int) $value);
            })
            ;

        $rawVideoHeights = collect($request->input('video_heights', []))
            ->map(function ($value) {
                if ($value === null || $value === '') {
                    return 0;
                }

                return max(0, (int) $value);
            })
            ;

        $playlist = collect(range(0, 9))
            ->map(function (int $index) use ($rawVideoUrls, $rawMutedFlags, $rawActiveFlags, $rawFullscreenFlags, $rawDurationSeconds, $rawVideoHeights) {
                $url = trim((string) ($rawVideoUrls->get($index) ?? ''));

                $isActive = (bool) ($rawActiveFlags->get($index) ?? false);

                return [
                    'url' => $url,
                    'muted' => (bool) ($rawMutedFlags->get($index) ?? false),
                    'active' => $url !== '' ? $isActive : false,
                    'fullscreen' => $url !== '' ? (bool) ($rawFullscreenFlags->get($index) ?? false) : false,
                    'durationSeconds' => $url !== '' ? (int) ($rawDurationSeconds->get($index) ?? 0) : 0,
                    'heightPx' => $url !== '' ? (int) ($rawVideoHeights->get($index) ?? 0) : 0,
                ];
            })
            ->values();

        $request->merge([
            'video_urls' => $rawVideoUrls->all(),
            'video_duration_seconds' => $rawDurationSeconds->all(),
            'video_heights' => $rawVideoHeights->all(),
            'videoUrl' => $playlist
                ->filter(fn ($item) => !empty($item['url']))
                ->pluck('url')
                ->implode("\n"),
            'videoPlaylist' => $playlist->all(),
        ]);

        $validated = $request->validate([
                        'rightSidebarImageHeight' => ['nullable', 'integer', 'min:0', 'max:1000'],
                        'rightSidebarImageWidth' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'videoUrl' => ['nullable', 'string', 'max:10000'],
            'videoPlaylist' => ['nullable', 'array', 'max:10'],
            'videoPlaylist.*.url' => ['nullable', 'url', 'max:1000'],
            'videoPlaylist.*.muted' => ['required', 'boolean'],
            'videoPlaylist.*.active' => ['required', 'boolean'],
            'videoPlaylist.*.fullscreen' => ['required', 'boolean'],
            'videoPlaylist.*.durationSeconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'videoPlaylist.*.heightPx' => ['required', 'integer', 'min:0', 'max:2000'],
            'video_urls' => ['nullable', 'array', 'max:10'],
            'video_urls.*' => ['nullable', 'url', 'max:1000'],
            'video_duration_seconds' => ['nullable', 'array', 'max:10'],
            'video_duration_seconds.*' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'video_heights' => ['nullable', 'array', 'max:10'],
            'video_heights.*' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'videoMuted' => ['nullable', 'boolean'],
            'showVideoPanel' => ['nullable', 'boolean'],
            'appBackgroundColor' => ['required', 'string', 'max:9'],
            'productsPanelBackgroundColor' => ['required', 'string', 'max:9'],
            'listBorderColor' => ['required', 'string', 'max:9'],
            'listBorderWidth' => ['nullable', 'integer', 'min:0', 'max:20'],
            'videoBackgroundColor' => ['required', 'string', 'max:9'],
            'showRightSidebarBorder' => ['nullable', 'boolean'],
            'rightSidebarBorderColor' => ['required', 'string', 'max:9'],
            'rightSidebarBorderWidth' => ['nullable', 'integer', 'min:0', 'max:20'],
            'rightSidebarMediaType' => ['required', 'in:video,image,hybrid'],
            'rightSidebarGlobalGalleryCode' => ['nullable', 'string', 'regex:/^\d{1,14}$/'],
            'rightSidebarImageUrls' => ['nullable', 'string', 'max:10000'],
            'suggestedProductImageSource' => ['nullable', 'string', 'regex:/^(none|slot_[1-3]|company_upload|company_existing_\d+)$/'],
            'suggestedSlideImageSources' => ['nullable', 'array'],
            'suggestedSlideImageSources.*' => ['string', 'regex:/^(slot_[1-3]|company_upload|company_existing_\d+)$/'],
            'selectedProductCode' => ['nullable', 'string', 'max:14'],
            'companyGalleryUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'rightSidebarImageInterval' => ['nullable', 'integer', 'min:1', 'max:300'],
            'rightSidebarImageFit' => ['required', 'in:contain,cover,scale-down'],
            'rightSidebarHybridVideoDuration' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rightSidebarHybridImageDuration' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'isVideoPanelTransparent' => ['nullable', 'boolean'],
            'rowBackgroundColor' => ['required', 'string', 'max:9'],
            'borderColor' => ['required', 'string', 'max:7'],
            'rowBorderWidth' => ['nullable', 'integer', 'min:0', 'max:20'],
            'priceColor' => ['required', 'string', 'max:7'],
            'useGradient' => ['nullable', 'boolean'],
            'gradientStartColor' => ['nullable', 'string', 'max:7'],
            'gradientEndColor' => ['nullable', 'string', 'max:7'],
            'backgroundImageUrl' => ['nullable', 'url', 'max:1000'],
            'showBorder' => ['nullable', 'boolean'],
            'isRowBorderTransparent' => ['nullable', 'boolean'],
            'showTitle' => ['nullable', 'boolean'],
            'showBackgroundImage' => ['nullable', 'boolean'],
            'showImage' => ['nullable', 'boolean'],
            'isProductsPanelTransparent' => ['nullable', 'boolean'],
            'isListBorderTransparent' => ['nullable', 'boolean'],
            'imageWidth' => ['nullable', 'integer', 'min:20', 'max:400'],
            'imageHeight' => ['nullable', 'integer', 'min:20', 'max:400'],
            'listFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelColor' => ['required', 'string', 'max:9'],
            'isPaginationEnabled' => ['nullable', 'boolean'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'paginationInterval' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $validated['useGradient'] = (bool) ($validated['useGradient'] ?? false);
        $validated['showBorder'] = (bool) ($validated['showBorder'] ?? false);
        $validated['isRowBorderTransparent'] = (bool) ($validated['isRowBorderTransparent'] ?? false);
        $validated['showTitle'] = (bool) ($validated['showTitle'] ?? true);
        $validated['showBackgroundImage'] = (bool) ($validated['showBackgroundImage'] ?? false);
        $validated['showImage'] = (bool) ($validated['showImage'] ?? true);
        $validated['isProductsPanelTransparent'] = (bool) ($validated['isProductsPanelTransparent'] ?? false);
        $validated['isListBorderTransparent'] = (bool) ($validated['isListBorderTransparent'] ?? false);
        $validated['isVideoPanelTransparent'] = (bool) ($validated['isVideoPanelTransparent'] ?? false);
        $validated['showVideoPanel'] = (bool) ($validated['showVideoPanel'] ?? true);
        $validated['showRightSidebarBorder'] = (bool) ($validated['showRightSidebarBorder'] ?? true);
        $validated['videoMuted'] = (bool) ($validated['videoMuted'] ?? false);
        $validated['isPaginationEnabled'] = (bool) ($validated['isPaginationEnabled'] ?? false);
        $validated['productsPanelBackgroundColor'] = (string) ($validated['productsPanelBackgroundColor'] ?? '#0f172a');
        $validated['listBorderColor'] = (string) ($validated['listBorderColor'] ?? '#334155');
        $validated['listBorderWidth'] = (int) ($validated['listBorderWidth'] ?? 1);
        $validated['videoBackgroundColor'] = (string) ($validated['videoBackgroundColor'] ?? '#000000');
        $validated['rightSidebarBorderColor'] = (string) ($validated['rightSidebarBorderColor'] ?? '#334155');
        $validated['rightSidebarBorderWidth'] = (int) ($validated['rightSidebarBorderWidth'] ?? 1);
        $validated['rightSidebarMediaType'] = (string) ($validated['rightSidebarMediaType'] ?? 'video');
        $validated['rightSidebarGlobalGalleryCode'] = substr(preg_replace('/\D/', '', (string) ($validated['rightSidebarGlobalGalleryCode'] ?? '')) ?? '', 0, 14);
        $validated['rightSidebarImageUrls'] = $this->normalizeImageUrlsList((string) ($validated['rightSidebarImageUrls'] ?? ''));
        $validated['suggestedProductImageSource'] = (string) ($validated['suggestedProductImageSource'] ?? 'none');
        $validated['suggestedSlideImageSources'] = array_values(array_unique((array) ($validated['suggestedSlideImageSources'] ?? [])));
        $validated['selectedProductCode'] = substr(preg_replace('/\D/', '', (string) ($validated['selectedProductCode'] ?? '')) ?? '', 0, 14);
        $validated['rightSidebarImageInterval'] = (int) ($validated['rightSidebarImageInterval'] ?? 8);
        $validated['rightSidebarImageFit'] = (string) ($validated['rightSidebarImageFit'] ?? 'scale-down');
        $validated['rightSidebarHybridVideoDuration'] = (int) ($validated['rightSidebarHybridVideoDuration'] ?? 2);
        $validated['rightSidebarHybridImageDuration'] = (int) ($validated['rightSidebarHybridImageDuration'] ?? 4);
        $validated['rowBorderWidth'] = (int) ($validated['rowBorderWidth'] ?? 1);
        $validated['rightSidebarImageHeight'] = (int) ($validated['rightSidebarImageHeight'] ?? 96);
        $validated['rightSidebarImageWidth'] = (int) ($validated['rightSidebarImageWidth'] ?? 0);
        $validated['listFontSize'] = (int) ($validated['listFontSize'] ?? 16);
        $validated['groupLabelFontSize'] = (int) ($validated['groupLabelFontSize'] ?? 14);
        $validated['pageSize'] = (int) ($validated['pageSize'] ?? 10);
        $validated['paginationInterval'] = (int) ($validated['paginationInterval'] ?? 5);
        unset($validated['video_urls']);
        unset($validated['video_duration_seconds']);
        unset($validated['video_heights']);

        if (! $validated['useGradient']) {
            $validated['gradientStartColor'] = $validated['rowBackgroundColor'];
            $validated['gradientEndColor'] = $validated['rowBackgroundColor'];
        }

        if (! $validated['showBackgroundImage']) {
            $validated['backgroundImageUrl'] = null;
        }

        $manualSlideUrls = collect(preg_split('/\r?\n/', (string) ($validated['rightSidebarImageUrls'] ?? '')) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();

        $availableGalleryImages = [];
        if ($validated['rightSidebarGlobalGalleryCode'] !== '') {
            $availableGalleryImages = $this->resolveGlobalGalleryImageUrlsBySlot($validated['rightSidebarGlobalGalleryCode']);
            $galleryImageUrls = array_values($availableGalleryImages);

            if (empty($galleryImageUrls)) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'rightSidebarGlobalGalleryCode' => 'Código da galeria geral não encontrado ou sem imagens cadastradas.',
                    ])
                    ->withInput();
            }
        } else {
            $validated['rightSidebarGlobalGalleryCode'] = null;
        }

        $companyUploadUrl = null;
        if ($request->hasFile('companyGalleryUpload')) {
            $companyUploadPath = $this->storeCompanyGalleryImage($request, $validated['rightSidebarGlobalGalleryCode'] ?: 'manual');
            $companyUploadUrl = $this->publicStorageUrl($companyUploadPath);
        }

        $availableSources = $availableGalleryImages;
        if ($companyUploadUrl) {
            $availableSources['company_upload'] = $companyUploadUrl;
        }

        $companyExistingImages = $this->listCompanyGalleryImages();
        foreach ($companyExistingImages as $index => $item) {
            $sourceKey = 'company_existing_'.$index;
            $availableSources[$sourceKey] = (string) ($item['url'] ?? '');
        }

        $selectedSlideUrls = collect($validated['suggestedSlideImageSources'])
            ->map(fn (string $sourceKey) => $availableSources[$sourceKey] ?? null)
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->values()
            ->all();

        $managedSourceUrls = collect($availableSources)
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->values()
            ->all();

        $manualSlideUrlsWithoutManaged = collect($manualSlideUrls)
            ->reject(fn ($url) => in_array($url, $managedSourceUrls, true))
            ->values()
            ->all();

        $finalSlideUrls = array_values(array_unique(array_merge($manualSlideUrlsWithoutManaged, $selectedSlideUrls)));

        if (empty($finalSlideUrls) && !empty($availableGalleryImages)) {
            $finalSlideUrls = array_values($availableGalleryImages);
        }

        $validated['rightSidebarImageUrls'] = implode("\n", $finalSlideUrls);

        $validated['rightSidebarImageUrls'] = $this->normalizeImageUrlsList((string) ($validated['rightSidebarImageUrls'] ?? ''));

        $selectedProductImageUrl = $availableSources[$validated['suggestedProductImageSource']] ?? null;
        if (is_string($selectedProductImageUrl) && $selectedProductImageUrl !== '' && $validated['selectedProductCode'] !== '') {
            $targetProduct = Produto::query()
                ->where('empresa_id', $empresaId)
                ->where('CODIGO', $validated['selectedProductCode'])
                ->first();

            if (! $targetProduct) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'selectedProductCode' => 'Produto não encontrado para o código informado na empresa logada.',
                    ])
                    ->withInput();
            }

            $targetProduct->update(['IMG' => $selectedProductImageUrl]);
        }

        if ($validated['rightSidebarMediaType'] === 'video') {
            $validated['rightSidebarImageUrls'] = null;
        }

        unset($validated['suggestedProductImageSource'], $validated['suggestedSlideImageSources'], $validated['selectedProductCode'], $validated['companyGalleryUpload']);

        $embedStatuses = $this->buildYouTubeEmbedStatuses($validated['videoPlaylist'] ?? []);
        $embedWarnings = collect($embedStatuses)
            ->filter(fn (array $item) => ($item['status'] ?? null) === 'blocked')
            ->map(fn (array $item) => (string) ($item['message'] ?? 'Bloqueio de incorporação detectado.'))
            ->values()
            ->all();

        Configuracao::updateOrCreate(
            ['empresa_id' => $empresaId],
            $validated
        );

        $redirect = redirect()
            ->back()
            ->with('success', 'Configuração da Tela Web atualizada com sucesso.');

        if (!empty($embedWarnings)) {
            $redirect->with('embedWarnings', $embedWarnings);
        }

        $redirect->with('embedStatuses', $embedStatuses);

        return $redirect;
    }

    private function resolveGlobalGalleryImageUrlsBySlot(string $code): array
    {
        $gallery = GlobalImageGallery::query()
            ->where('code', $code)
            ->with('items')
            ->first();

        if (! $gallery) {
            return [];
        }

        return $gallery->items
            ->sortBy('slot')
            ->mapWithKeys(function ($item) {
                $slotKey = 'slot_'.(int) $item->slot;
                if ($item->source_type === 'link') {
                    return [$slotKey => trim((string) ($item->external_url ?? ''))];
                }

                if ($item->source_type === 'upload' && !empty($item->file_path)) {
                    return [$slotKey => $this->publicStorageUrl((string) $item->file_path)];
                }

                return [$slotKey => ''];
            })
            ->filter(fn ($url) => $url !== '')
            ->all();
    }

    private function storeCompanyGalleryImage(Request $request, string $code): string
    {
        $normalizedCode = substr(preg_replace('/\D/', '', $code) ?? 'manual', 0, 14) ?: 'manual';
        $document = $this->resolveCompanyStorageDocument();

        $upload = $request->file('companyGalleryUpload');
        $extension = strtolower((string) $upload->getClientOriginalExtension());
        $fileName = $normalizedCode.'_empresa_'.time().($extension ? '.'.$extension : '');

        return $upload->storeAs('empresas/'.$document.'/galeria', $fileName, 'public');
    }

    private function listCompanyGalleryImages(): array
    {
        $documents = $this->resolveCompanyStorageDocuments();

        $files = collect($documents)
            ->map(fn (string $document) => 'empresas/'.$document.'/galeria')
            ->flatMap(fn (string $directory) => Storage::disk('public')->exists($directory) ? Storage::disk('public')->files($directory) : [])
            ->unique()
            ->sortByDesc(fn (string $path) => Storage::disk('public')->lastModified($path))
            ->values();

        return $files
            ->map(fn (string $path) => [
                'path' => $path,
                'url' => $this->publicStorageUrl($path),
                'name' => basename($path),
            ])
            ->all();
    }

    private function publicStorageUrl(string $path): string
    {
        $relativePath = ltrim($path, '/');

        return '/storage/'.$relativePath;
    }

    private function normalizeImageUrlsList(string $raw): string
    {
        return collect(preg_split('/\r?\n/', $raw) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->map(function (string $line) {
                if (preg_match('#^https?://localhost/storage/(.+)$#i', $line, $matches)) {
                    return '/storage/'.ltrim((string) ($matches[1] ?? ''), '/');
                }

                if (str_starts_with($line, 'storage/')) {
                    return '/'.ltrim($line, '/');
                }

                return $line;
            })
            ->values()
            ->implode("\n");
    }

    private function resolveCompanyStorageDocuments(): array
    {
        $companyDocument = $this->resolveCompanyStorageDocument();
        $userDocument = preg_replace('/\D/', '', (string) (Auth::user()?->documento() ?? '')) ?: null;

        return collect([$companyDocument, $userDocument, 'sem-documento'])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveCompanyStorageDocument(): string
    {
        $empresaId = $this->resolveEmpresaId();

        $companyDocument = Empresa::query()
            ->where('id', $empresaId)
            ->value('cnpj_cpf');

        return preg_replace('/\D/', '', (string) $companyDocument) ?: 'sem-documento';
    }

    private function resolveEmpresaId(): int
    {
        $user = Auth::user();

        if (! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        return (int) $user->empresa_id;
    }

    private function buildYouTubeEmbedStatuses(array $playlist): array
    {
        $statuses = [];

        foreach ($playlist as $index => $item) {
            $url = trim((string) ($item['url'] ?? ''));

            if ($url === '') {
                $statuses[$index] = [
                    'status' => 'empty',
                    'message' => null,
                ];
                continue;
            }

            $videoId = $this->extractYouTubeVideoId($url);
            if ($videoId === null) {
                $statuses[$index] = [
                    'status' => 'unknown',
                    'message' => 'Não foi possível validar automaticamente (link não-YouTube).',
                ];
                continue;
            }

            $result = $this->checkYouTubeEmbeddable($videoId);
            $statuses[$index] = [
                'status' => $result['embeddable'] ? 'likely' : 'blocked',
                'message' => $result['embeddable']
                    ? 'Vídeo '.($index + 1).': sem bloqueio detectado no servidor (pode variar por dispositivo/região).'
                    : 'Vídeo '.($index + 1).': '.$result['reason'],
            ];
        }

        return $statuses;
    }

    private function extractYouTubeVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (str_contains($host, 'youtu.be')) {
            $id = trim($path, '/');
            return $id !== '' ? $id : null;
        }

        if (! str_contains($host, 'youtube.com')) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        if (! empty($query['v'])) {
            return (string) $query['v'];
        }

        if (preg_match('#/(?:embed|shorts|live)/([^/?]+)#', $path, $matches) === 1) {
            return (string) $matches[1];
        }

        return null;
    }

    private function checkYouTubeEmbeddable(string $videoId): array
    {
        $watchUrl = 'https://www.youtube.com/watch?v='.$videoId;

        try {
            $oembed = Http::timeout(8)
                ->acceptJson()
                ->get('https://www.youtube.com/oembed', [
                    'url' => $watchUrl,
                    'format' => 'json',
                ]);

            if (! $oembed->successful()) {
                return [
                    'embeddable' => false,
                    'reason' => 'YouTube não confirmou incorporação (oEmbed).',
                ];
            }

            $embedHtml = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get('https://www.youtube.com/embed/'.$videoId.'?autoplay=1&mute=1');

            if (! $embedHtml->successful()) {
                return [
                    'embeddable' => false,
                    'reason' => 'Player embed retornou erro HTTP.',
                ];
            }

            $html = strtolower($embedHtml->body());
            $blockedSignals = [
                'playback on other websites has been disabled',
                'a reprodução em outros websites foi desativada',
                'watch on youtube',
                'assistir no youtube',
                'video unavailable',
                'vídeo indisponível',
            ];

            foreach ($blockedSignals as $signal) {
                if (str_contains($html, $signal)) {
                    return [
                        'embeddable' => false,
                        'reason' => 'YouTube sinalizou bloqueio de incorporação para este vídeo.',
                    ];
                }
            }

            return [
                'embeddable' => true,
                'reason' => null,
            ];
        } catch (Throwable) {
            return [
                'embeddable' => false,
                'reason' => 'Não foi possível validar incorporação agora (timeout/rede).',
            ];
        }
    }

    private function extractVideoUrlFromInput(string $input): string
    {
        $value = trim($input);
        if ($value === '') {
            return '';
        }

        if (preg_match('/src=["\']([^"\']+)["\']/i', $value, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        if (preg_match('/https?:\/\/[^\s"\'<>]+/i', $value, $matches) === 1) {
            return trim((string) $matches[0]);
        }

        return $value;
    }
}
