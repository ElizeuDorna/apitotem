<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\GlobalImageGallery;
use App\Models\Grupo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
            'availableGroups' => Grupo::query()
                ->where('empresa_id', $empresaId)
                ->orderBy('nome')
                ->get(['id', 'nome']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $empresaId = $this->resolveEmpresaId();
        $saveSection = trim((string) $request->input('saveSection', ''));
        $shouldProcessRightSidebarMedia = in_array($saveSection, ['', 'companyGalleryConfigSection'], true);
        $shouldProcessGeneralConfig = in_array($saveSection, ['', 'generalConfigSection'], true);
        $shouldProcessVideoValidation = in_array($saveSection, ['', 'videoConfigSection'], true);
        $slideSelectionSubmitted = (bool) $request->boolean('suggestedSlideSelectionSubmitted', false);
        $currentConfig = Configuracao::firstOrCreate(['empresa_id' => $empresaId], []);

        if ($saveSection !== '') {
            $this->hydrateMissingInputsFromCurrentConfig($request, $currentConfig);
        }

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
            'saveSection' => ['nullable', 'in:generalConfigSection,videoConfigSection,colorConfigSection,rightSidebarConfigSection,companyGalleryConfigSection,imageSizeConfigSection,paginationConfigSection'],
            'rightSidebarImageHeight' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarImageWidth' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarAndroidHeight' => ['nullable', 'integer', 'min:0', 'max:1500'],
            'rightSidebarAndroidWidth' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarAndroidVerticalOffset' => ['nullable', 'integer', 'min:-300', 'max:300'],
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
            'showRightSidebarPanel' => ['nullable', 'boolean'],
            'showRightSidebarLogo' => ['nullable', 'boolean'],
            'rightSidebarLogoPosition' => ['nullable', 'in:sidebar_top,screen_right_vertical'],
            'showLeftVerticalLogo' => ['nullable', 'boolean'],
            'isMainBorderEnabled' => ['nullable', 'boolean'],
            'isRoundedCornersEnabled' => ['nullable', 'boolean'],
            'mainBorderColor' => ['required', 'string', 'max:9'],
            'mainBorderWidth' => ['nullable', 'integer', 'min:0', 'max:40'],
            'appBackgroundColor' => ['required', 'string', 'max:9'],
            'productsPanelBackgroundColor' => ['required', 'string', 'max:9'],
            'listBorderColor' => ['required', 'string', 'max:9'],
            'listBorderWidth' => ['nullable', 'integer', 'min:0', 'max:20'],
            'videoBackgroundColor' => ['nullable', 'string', 'max:9'],
            'showRightSidebarBorder' => ['nullable', 'boolean'],
            'rightSidebarBorderColor' => ['required', 'string', 'max:9'],
            'rightSidebarBorderWidth' => ['nullable', 'integer', 'min:0', 'max:20'],
            'rightSidebarMediaType' => ['nullable', 'in:video,image,hybrid'],
            'rightSidebarGlobalGalleryCode' => ['nullable', 'string', 'regex:/^\d{1,14}$/'],
            'rightSidebarImageUrls' => ['nullable', 'string', 'max:10000'],
            'rightSidebarImageSchedules' => ['nullable', 'array'],
            'rightSidebarImageSchedules.*.url' => ['nullable', 'string', 'max:1000'],
            'rightSidebarImageSchedules.*.startDate' => ['nullable', 'date_format:Y-m-d'],
            'rightSidebarImageSchedules.*.endDate' => ['nullable', 'date_format:Y-m-d'],
            'suggestedSlideImageSources' => ['nullable', 'array'],
            'suggestedSlideImageSources.*' => ['string', 'regex:/^(slot_[1-3]|company_upload|company_existing_\d+)$/'],
            'suggestedSlideSelectionSubmitted' => ['nullable', 'boolean'],
            'companyGalleryUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'rightSidebarLogoUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'rightSidebarLogoUrl' => ['nullable', 'string', 'max:1000'],
            'rightSidebarLogoWidth' => ['nullable', 'integer', 'min:60', 'max:1200'],
            'rightSidebarLogoHeight' => ['nullable', 'integer', 'min:30', 'max:300'],
            'leftVerticalLogoUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'leftVerticalLogoUrl' => ['nullable', 'string', 'max:1000'],
            'leftVerticalLogoWidth' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'leftVerticalLogoHeight' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'rightSidebarLogoBackgroundColor' => ['nullable', 'string', 'max:9'],
            'isRightSidebarLogoBackgroundTransparent' => ['nullable', 'boolean'],
            'rightSidebarImageInterval' => ['nullable', 'integer', 'min:1', 'max:300'],
            'rightSidebarImageFit' => ['required', 'in:contain,cover,scale-down'],
            'rightSidebarHybridVideoDuration' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rightSidebarHybridImageDuration' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'productListType' => ['nullable', 'in:1,2'],
            'productListLeftGroupIds' => ['nullable', 'array'],
            'productListLeftGroupIds.*' => ['integer'],
            'productListRightGroupIds' => ['nullable', 'array'],
            'productListRightGroupIds.*' => ['integer'],
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
            'titleText' => ['nullable', 'string', 'max:120'],
            'isTitleDynamic' => ['nullable', 'boolean'],
            'titlePosition' => ['nullable', 'in:top,footer'],
            'titleFontSize' => ['nullable', 'integer', 'min:10', 'max:96'],
            'titleFontFamily' => ['nullable', 'in:arial,verdana,tahoma,trebuchet,georgia,courier,system'],
            'titleTextColor' => ['nullable', 'string', 'max:9'],
            'titleBackgroundColor' => ['nullable', 'string', 'max:9'],
            'isTitleBackgroundTransparent' => ['nullable', 'boolean'],
            'showTitleBorder' => ['nullable', 'boolean'],
            'showBackgroundImage' => ['nullable', 'boolean'],
            'showImage' => ['nullable', 'boolean'],
            'isRowRoundedEnabled' => ['nullable', 'boolean'],
            'isProductsPanelTransparent' => ['nullable', 'boolean'],
            'isListBorderTransparent' => ['nullable', 'boolean'],
            'imageWidth' => ['nullable', 'integer', 'min:20', 'max:400'],
            'imageHeight' => ['nullable', 'integer', 'min:20', 'max:400'],
            'rowVerticalPadding' => ['nullable', 'integer', 'min:0', 'max:40'],
            'listFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelFontFamily' => ['nullable', 'in:arial,verdana,tahoma,trebuchet,georgia,courier,system'],
            'groupLabelColor' => ['required', 'string', 'max:9'],
            'showGroupLabelBadge' => ['nullable', 'boolean'],
            'groupLabelBadgeColor' => ['required', 'string', 'max:9'],
            'isPaginationEnabled' => ['nullable', 'boolean'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'paginationInterval' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $validated['useGradient'] = (bool) ($validated['useGradient'] ?? false);
        $validated['showBorder'] = (bool) ($validated['showBorder'] ?? false);
        $validated['isRowBorderTransparent'] = (bool) ($validated['isRowBorderTransparent'] ?? false);
        $validated['showTitle'] = (bool) ($validated['showTitle'] ?? true);
        $validated['titleText'] = trim((string) ($validated['titleText'] ?? ''));
        $validated['isTitleDynamic'] = (bool) ($validated['isTitleDynamic'] ?? false);
        $validated['titlePosition'] = (string) ($validated['titlePosition'] ?? 'top');
        $validated['titleFontSize'] = (int) ($validated['titleFontSize'] ?? 32);
        $validated['titleFontFamily'] = (string) ($validated['titleFontFamily'] ?? 'arial');
        $validated['titleTextColor'] = (string) ($validated['titleTextColor'] ?? '#f8fafc');
        $validated['titleBackgroundColor'] = (string) ($validated['titleBackgroundColor'] ?? '#0f172a');
        $validated['isTitleBackgroundTransparent'] = (bool) ($validated['isTitleBackgroundTransparent'] ?? false);
        $validated['showTitleBorder'] = (bool) ($validated['showTitleBorder'] ?? true);
        $validated['showBackgroundImage'] = (bool) ($validated['showBackgroundImage'] ?? false);
        $validated['showImage'] = (bool) ($validated['showImage'] ?? true);
        $validated['isRowRoundedEnabled'] = (bool) ($validated['isRowRoundedEnabled'] ?? false);
        $validated['isProductsPanelTransparent'] = (bool) ($validated['isProductsPanelTransparent'] ?? false);
        $validated['isListBorderTransparent'] = (bool) ($validated['isListBorderTransparent'] ?? false);
        $validated['isVideoPanelTransparent'] = (bool) ($validated['isVideoPanelTransparent'] ?? false);
        $validated['showVideoPanel'] = (bool) ($validated['showVideoPanel'] ?? true);
        $validated['showRightSidebarPanel'] = (bool) ($validated['showRightSidebarPanel'] ?? true);
        $validated['showRightSidebarLogo'] = (bool) ($validated['showRightSidebarLogo'] ?? false);
        $validated['rightSidebarLogoPosition'] = 'sidebar_top';
        $validated['showLeftVerticalLogo'] = (bool) ($validated['showLeftVerticalLogo'] ?? false);
        $validated['rightSidebarLogoUrl'] = trim((string) ($validated['rightSidebarLogoUrl'] ?? ''));
        $validated['rightSidebarLogoWidth'] = (int) ($validated['rightSidebarLogoWidth'] ?? 220);
        $validated['rightSidebarLogoHeight'] = (int) ($validated['rightSidebarLogoHeight'] ?? 58);
        $validated['leftVerticalLogoUrl'] = trim((string) ($validated['leftVerticalLogoUrl'] ?? ''));
        $validated['leftVerticalLogoWidth'] = (int) ($validated['leftVerticalLogoWidth'] ?? 120);
        $validated['leftVerticalLogoHeight'] = (int) ($validated['leftVerticalLogoHeight'] ?? 220);
        $validated['rightSidebarLogoBackgroundColor'] = (string) ($validated['rightSidebarLogoBackgroundColor'] ?? '#0f172a');
        $validated['isRightSidebarLogoBackgroundTransparent'] = (bool) ($validated['isRightSidebarLogoBackgroundTransparent'] ?? false);
        $validated['isMainBorderEnabled'] = (bool) ($validated['isMainBorderEnabled'] ?? false);
        $validated['isRoundedCornersEnabled'] = (bool) ($validated['isRoundedCornersEnabled'] ?? true);
        $validated['showRightSidebarBorder'] = (bool) ($validated['showRightSidebarBorder'] ?? true);
        $validated['videoMuted'] = (bool) ($validated['videoMuted'] ?? false);
        $validated['isPaginationEnabled'] = (bool) ($validated['isPaginationEnabled'] ?? false);
        $validated['mainBorderColor'] = (string) ($validated['mainBorderColor'] ?? '#000000');
        $validated['mainBorderWidth'] = (int) ($validated['mainBorderWidth'] ?? 1);
        $validated['productsPanelBackgroundColor'] = (string) ($validated['productsPanelBackgroundColor'] ?? '#0f172a');
        $validated['listBorderColor'] = (string) ($validated['listBorderColor'] ?? '#334155');
        $validated['listBorderWidth'] = (int) ($validated['listBorderWidth'] ?? 1);
        $validated['videoBackgroundColor'] = (string) ($validated['videoBackgroundColor'] ?? '#000000');
        $validated['rightSidebarBorderColor'] = (string) ($validated['rightSidebarBorderColor'] ?? '#334155');
        $validated['rightSidebarBorderWidth'] = (int) ($validated['rightSidebarBorderWidth'] ?? 1);
        $validated['rightSidebarMediaType'] = (string) ($validated['rightSidebarMediaType'] ?? 'video');
        $validated['rightSidebarGlobalGalleryCode'] = substr(preg_replace('/\D/', '', (string) ($validated['rightSidebarGlobalGalleryCode'] ?? '')) ?? '', 0, 14);
        $validated['rightSidebarImageUrls'] = $this->normalizeImageUrlsList((string) ($validated['rightSidebarImageUrls'] ?? ''));
        $validated['rightSidebarImageSchedules'] = collect((array) ($validated['rightSidebarImageSchedules'] ?? []))
            ->map(function (array $item) {
                $normalizedUrl = $this->normalizeSingleImageUrl((string) ($item['url'] ?? ''));
                $startDate = trim((string) ($item['startDate'] ?? ''));
                $endDate = trim((string) ($item['endDate'] ?? ''));

                if ($normalizedUrl === '') {
                    return null;
                }

                if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
                    $endDate = $startDate;
                }

                return [
                    'url' => $normalizedUrl,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ];
            })
            ->filter(fn ($item) => is_array($item))
            ->unique(fn (array $item) => (string) ($item['url'] ?? ''))
            ->values()
            ->all();
        $validated['suggestedSlideImageSources'] = array_values(array_unique((array) ($validated['suggestedSlideImageSources'] ?? [])));
        $validated['rightSidebarImageInterval'] = (int) ($validated['rightSidebarImageInterval'] ?? 8);
        $validated['rightSidebarImageFit'] = (string) ($validated['rightSidebarImageFit'] ?? 'scale-down');
        $validated['rightSidebarHybridVideoDuration'] = (int) ($validated['rightSidebarHybridVideoDuration'] ?? 2);
        $validated['rightSidebarHybridImageDuration'] = (int) ($validated['rightSidebarHybridImageDuration'] ?? 4);
        $validated['productListType'] = (string) ($validated['productListType'] ?? '1');
        $validGroupIds = Grupo::query()
            ->where('empresa_id', $empresaId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $validated['productListLeftGroupIds'] = collect((array) ($validated['productListLeftGroupIds'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $validGroupIds, true))
            ->unique()
            ->values()
            ->all();
        $validated['productListRightGroupIds'] = collect((array) ($validated['productListRightGroupIds'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $validGroupIds, true))
            ->reject(fn ($id) => in_array($id, $validated['productListLeftGroupIds'], true))
            ->unique()
            ->values()
            ->all();
        $validated['rowBorderWidth'] = (int) ($validated['rowBorderWidth'] ?? 1);
        $validated['rightSidebarImageHeight'] = (int) ($validated['rightSidebarImageHeight'] ?? 96);
        $validated['rightSidebarImageWidth'] = (int) ($validated['rightSidebarImageWidth'] ?? 0);
        $validated['rightSidebarAndroidHeight'] = (int) ($validated['rightSidebarAndroidHeight'] ?? 0);
        $validated['rightSidebarAndroidWidth'] = (int) ($validated['rightSidebarAndroidWidth'] ?? 0);
        $validated['rightSidebarAndroidVerticalOffset'] = (int) ($validated['rightSidebarAndroidVerticalOffset'] ?? 0);
        $validated['rowVerticalPadding'] = (int) ($validated['rowVerticalPadding'] ?? 9);
        $validated['listFontSize'] = (int) ($validated['listFontSize'] ?? 16);
        $validated['groupLabelFontSize'] = (int) ($validated['groupLabelFontSize'] ?? 14);
        $validated['groupLabelFontFamily'] = (string) ($validated['groupLabelFontFamily'] ?? 'arial');
        $validated['pageSize'] = (int) ($validated['pageSize'] ?? 10);
        $validated['paginationInterval'] = (int) ($validated['paginationInterval'] ?? 5);
        $validated['showGroupLabelBadge'] = (bool) ($validated['showGroupLabelBadge'] ?? false);
        $validated['groupLabelBadgeColor'] = (string) ($validated['groupLabelBadgeColor'] ?? '#0f172a');

        if ($validated['titleText'] === '') {
            $validated['titleText'] = 'Lista de Produtos (TV)';
        }

        unset($validated['video_urls']);
        unset($validated['video_duration_seconds']);
        unset($validated['video_heights']);
        unset($validated['saveSection']);

        if (! $validated['useGradient']) {
            $validated['gradientStartColor'] = $validated['rowBackgroundColor'];
            $validated['gradientEndColor'] = $validated['rowBackgroundColor'];
        }

        if (! $validated['showBackgroundImage']) {
            $validated['backgroundImageUrl'] = null;
        }

        if ($validated['showRightSidebarPanel'] && $validated['productListType'] === '2') {
            $validated['productListType'] = '1';
        }

        // Backward compatibility: if migration has not run yet, avoid writing unknown column.
        if (! Schema::hasColumn('configuracoes', 'showTitleBorder')) {
            unset($validated['showTitleBorder']);
        }

        if (! Schema::hasColumn('configuracoes', 'showRightSidebarLogo')) {
            unset($validated['showRightSidebarLogo']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoPosition')) {
            unset($validated['rightSidebarLogoPosition']);
        }

        if (! Schema::hasColumn('configuracoes', 'showLeftVerticalLogo')) {
            unset($validated['showLeftVerticalLogo']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoUrl')) {
            unset($validated['rightSidebarLogoUrl']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoWidth')) {
            unset($validated['rightSidebarLogoWidth']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoHeight')) {
            unset($validated['rightSidebarLogoHeight']);
        }

        if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoUrl')) {
            unset($validated['leftVerticalLogoUrl']);
        }

        if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoWidth')) {
            unset($validated['leftVerticalLogoWidth']);
        }

        if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoHeight')) {
            unset($validated['leftVerticalLogoHeight']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoBackgroundColor')) {
            unset($validated['rightSidebarLogoBackgroundColor']);
        }

        if (! Schema::hasColumn('configuracoes', 'isRightSidebarLogoBackgroundTransparent')) {
            unset($validated['isRightSidebarLogoBackgroundTransparent']);
        }

        if ($shouldProcessGeneralConfig && $request->hasFile('rightSidebarLogoUpload') && Schema::hasColumn('configuracoes', 'rightSidebarLogoUrl')) {
            $currentLogoPath = $this->extractStoragePathFromPublicUrl((string) ($currentConfig->rightSidebarLogoUrl ?? ''));
            if ($currentLogoPath !== '' && Storage::disk('public')->exists($currentLogoPath)) {
                Storage::disk('public')->delete($currentLogoPath);
            }

            $logoPath = $this->storeRightSidebarLogo($request);
            $validated['rightSidebarLogoUrl'] = $this->publicStorageUrl($logoPath);
        }

        if ($shouldProcessGeneralConfig && $request->hasFile('leftVerticalLogoUpload') && Schema::hasColumn('configuracoes', 'leftVerticalLogoUrl')) {
            $currentLeftLogoPath = $this->extractStoragePathFromPublicUrl((string) ($currentConfig->leftVerticalLogoUrl ?? ''));
            if ($currentLeftLogoPath !== '' && Storage::disk('public')->exists($currentLeftLogoPath)) {
                Storage::disk('public')->delete($currentLeftLogoPath);
            }

            $leftLogoPath = $this->storeLeftVerticalLogo($request);
            $validated['leftVerticalLogoUrl'] = $this->publicStorageUrl($leftLogoPath);
        }


        if ($shouldProcessRightSidebarMedia) {
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

            $managedSourceUrls = collect($availableSources)
                ->filter(fn ($url) => is_string($url) && $url !== '')
                ->values()
                ->all();

            $finalSlideUrls = $manualSlideUrls;

            if ($slideSelectionSubmitted) {
                $selectedSlideUrls = collect($validated['suggestedSlideImageSources'])
                    ->map(fn (string $sourceKey) => $availableSources[$sourceKey] ?? null)
                    ->filter(fn ($url) => is_string($url) && $url !== '')
                    ->values()
                    ->all();

                $manualSlideUrlsWithoutManaged = collect($manualSlideUrls)
                    ->reject(fn ($url) => in_array($url, $managedSourceUrls, true))
                    ->values()
                    ->all();

                $finalSlideUrls = array_values(array_unique(array_merge($manualSlideUrlsWithoutManaged, $selectedSlideUrls)));
            }

            if (empty($finalSlideUrls) && !empty($availableGalleryImages)) {
                $finalSlideUrls = array_values($availableGalleryImages);
            }

            $validated['rightSidebarImageUrls'] = implode("\n", $finalSlideUrls);

            $validated['rightSidebarImageUrls'] = $this->normalizeImageUrlsList((string) ($validated['rightSidebarImageUrls'] ?? ''));
            $finalSlideUrlSet = collect(preg_split('/\r?\n/', (string) ($validated['rightSidebarImageUrls'] ?? '')) ?: [])
                ->map(fn (string $line) => $this->normalizeSingleImageUrl($line))
                ->filter(fn (string $line) => $line !== '')
                ->values()
                ->all();

            $validated['rightSidebarImageSchedules'] = collect((array) ($validated['rightSidebarImageSchedules'] ?? []))
                ->filter(fn (array $item) => in_array((string) ($item['url'] ?? ''), $finalSlideUrlSet, true))
                ->values()
                ->all();

        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarImageSchedules')) {
            unset($validated['rightSidebarImageSchedules']);
        }

        if ($validated['rightSidebarMediaType'] === 'video') {
            $validated['rightSidebarImageUrls'] = null;
        }

        unset($validated['suggestedSlideImageSources'], $validated['suggestedSlideSelectionSubmitted'], $validated['companyGalleryUpload'], $validated['rightSidebarLogoUpload'], $validated['leftVerticalLogoUpload']);

        $embedStatuses = [];
        $embedWarnings = [];
        if ($shouldProcessVideoValidation) {
            $embedStatuses = $this->buildYouTubeEmbedStatuses($validated['videoPlaylist'] ?? []);
            $embedWarnings = collect($embedStatuses)
                ->filter(fn (array $item) => ($item['status'] ?? null) === 'blocked')
                ->map(fn (array $item) => (string) ($item['message'] ?? 'Bloqueio de incorporação detectado.'))
                ->values()
                ->all();
        }

        Configuracao::updateOrCreate(
            ['empresa_id' => $empresaId],
            $validated
        );

        $redirect = redirect()->back();

        if ($saveSection !== '') {
            $redirect->with('success', 'Menu salvo com sucesso.');
        } else {
            $redirect->with('success', 'Configuração da Tela Web atualizada com sucesso.');
        }

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

    private function hydrateMissingInputsFromCurrentConfig(Request $request, Configuracao $config): void
    {
        $scalarFields = [
            'showVideoPanel',
            'showRightSidebarPanel',
            'showRightSidebarLogo',
            'rightSidebarLogoPosition',
            'showLeftVerticalLogo',
            'rightSidebarLogoUrl',
            'rightSidebarLogoWidth',
            'rightSidebarLogoHeight',
            'leftVerticalLogoUrl',
            'leftVerticalLogoWidth',
            'leftVerticalLogoHeight',
            'isMainBorderEnabled',
            'isRoundedCornersEnabled',
            'mainBorderColor',
            'mainBorderWidth',
            'appBackgroundColor',
            'productsPanelBackgroundColor',
            'listBorderColor',
            'listBorderWidth',
            'videoBackgroundColor',
            'showRightSidebarBorder',
            'rightSidebarBorderColor',
            'rightSidebarBorderWidth',
            'rightSidebarMediaType',
            'rightSidebarGlobalGalleryCode',
            'rightSidebarImageUrls',
            'rightSidebarImageInterval',
            'rightSidebarImageFit',
            'rightSidebarImageHeight',
            'rightSidebarImageWidth',
            'rightSidebarAndroidHeight',
            'rightSidebarAndroidWidth',
            'rightSidebarAndroidVerticalOffset',
            'rightSidebarHybridVideoDuration',
            'rightSidebarHybridImageDuration',
            'productListType',
            'isVideoPanelTransparent',
            'rowBackgroundColor',
            'borderColor',
            'rowBorderWidth',
            'isRowBorderTransparent',
            'priceColor',
            'useGradient',
            'gradientStartColor',
            'gradientEndColor',
            'backgroundImageUrl',
            'showBorder',
            'showTitle',
            'titleText',
            'isTitleDynamic',
            'titlePosition',
            'titleFontSize',
            'titleFontFamily',
            'titleTextColor',
            'titleBackgroundColor',
            'isTitleBackgroundTransparent',
            'showTitleBorder',
            'showBackgroundImage',
            'showImage',
            'isRowRoundedEnabled',
            'isProductsPanelTransparent',
            'isListBorderTransparent',
            'imageWidth',
            'imageHeight',
            'rowVerticalPadding',
            'listFontSize',
            'groupLabelFontSize',
            'groupLabelFontFamily',
            'groupLabelColor',
            'showGroupLabelBadge',
            'groupLabelBadgeColor',
            'isPaginationEnabled',
            'pageSize',
            'paginationInterval',
            'videoMuted',
                'rightSidebarLogoBackgroundColor',
                'isRightSidebarLogoBackgroundTransparent',
        ];

        $merge = [];
        foreach ($scalarFields as $field) {
            if (! $request->has($field)) {
                $merge[$field] = $config->{$field};
            }
        }

        if (! $request->has('productListLeftGroupIds')) {
            $merge['productListLeftGroupIds'] = (array) ($config->productListLeftGroupIds ?? []);
        }

        if (! $request->has('productListRightGroupIds')) {
            $merge['productListRightGroupIds'] = (array) ($config->productListRightGroupIds ?? []);
        }

        if (! $request->has('rightSidebarImageSchedules')) {
            $merge['rightSidebarImageSchedules'] = (array) ($config->rightSidebarImageSchedules ?? []);
        }

        if (! $request->has('suggestedSlideImageSources')) {
            $merge['suggestedSlideImageSources'] = [];
        }

        if (! $request->has('video_urls')) {
            $playlist = collect($config->videoPlaylist ?? [])->values();
            $merge['video_urls'] = collect(range(0, 9))->map(fn (int $index) => (string) ($playlist->get($index)['url'] ?? ''))->all();
            $merge['video_muted_flags'] = collect(range(0, 9))->map(fn (int $index) => (int) ((bool) ($playlist->get($index)['muted'] ?? false)))->all();
            $merge['video_active_flags'] = collect(range(0, 9))->map(fn (int $index) => (int) ((bool) ($playlist->get($index)['active'] ?? false)))->all();
            $merge['video_fullscreen_flags'] = collect(range(0, 9))->map(fn (int $index) => (int) ((bool) ($playlist->get($index)['fullscreen'] ?? false)))->all();
            $merge['video_duration_seconds'] = collect(range(0, 9))->map(fn (int $index) => (int) ($playlist->get($index)['durationSeconds'] ?? 0))->all();
            $merge['video_heights'] = collect(range(0, 9))->map(fn (int $index) => (int) ($playlist->get($index)['heightPx'] ?? 0))->all();
        }

        if (! empty($merge)) {
            $request->merge($merge);
        }
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

    private function storeRightSidebarLogo(Request $request): string
    {
        $document = $this->resolveCompanyStorageDocument();
        $upload = $request->file('rightSidebarLogoUpload');
        $extension = strtolower((string) $upload->getClientOriginalExtension());
        $fileName = 'tv_logo_'.time().($extension ? '.'.$extension : '');

        return $upload->storeAs('empresas/'.$document.'/tv', $fileName, 'public');
    }

    private function storeLeftVerticalLogo(Request $request): string
    {
        $document = $this->resolveCompanyStorageDocument();
        $upload = $request->file('leftVerticalLogoUpload');
        $extension = strtolower((string) $upload->getClientOriginalExtension());
        $fileName = 'tv_left_logo_'.time().($extension ? '.'.$extension : '');

        return $upload->storeAs('empresas/'.$document.'/tv', $fileName, 'public');
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

    private function extractStoragePathFromPublicUrl(string $url): string
    {
        $value = trim($url);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/storage/')) {
            return ltrim(substr($value, 9), '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return ltrim(substr($value, 8), '/');
        }

        return '';
    }

    private function normalizeImageUrlsList(string $raw): string
    {
        return collect(preg_split('/\r?\n/', $raw) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->map(fn (string $line) => $this->normalizeSingleImageUrl($line))
            ->values()
            ->implode("\n");
    }

    private function normalizeSingleImageUrl(string $raw): string
    {
        $line = trim($raw);

        if ($line === '') {
            return '';
        }

        if (preg_match('#^https?://localhost/storage/(.+)$#i', $line, $matches)) {
            return '/storage/'.ltrim((string) ($matches[1] ?? ''), '/');
        }

        if (str_starts_with($line, 'storage/')) {
            return '/'.ltrim($line, '/');
        }

        return $line;
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
