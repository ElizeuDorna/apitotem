<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\GlobalImageGallery;
use App\Models\Grupo;
use App\Support\EmpresaContext;
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
        if ($config->rightSidebarImageUrls === '') {
            $scheduleUrls = collect((array) ($config->rightSidebarImageSchedules ?? []))
                ->map(fn (array $item) => $this->normalizeSingleImageUrl((string) ($item['url'] ?? '')))
                ->filter(fn (string $url) => $url !== '')
                ->unique()
                ->values()
                ->all();

            if (!empty($scheduleUrls)) {
                $config->rightSidebarImageUrls = implode("\n", $scheduleUrls);
            }
        }
        $config->fullScreenSlideImageUrls = $this->normalizeImageUrlsList((string) ($config->fullScreenSlideImageUrls ?? ''));

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
        $shouldProcessRightSidebarMedia = in_array($saveSection, ['', 'companyGalleryConfigSection', 'fullScreenSlideConfig'], true);
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
            ->map(function (int $index) use ($rawVideoUrls, $rawMutedFlags, $rawActiveFlags, $rawDurationSeconds, $rawVideoHeights) {
                $url = trim((string) ($rawVideoUrls->get($index) ?? ''));

                $isActive = (bool) ($rawActiveFlags->get($index) ?? false);

                return [
                    'url' => $url,
                    'muted' => (bool) ($rawMutedFlags->get($index) ?? false),
                    'active' => $url !== '' ? $isActive : false,
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
            'saveSection' => ['nullable', 'in:generalConfigSection,videoConfigSection,colorConfigSection,rightSidebarConfigSection,companyGalleryConfigSection,fullScreenSlideConfig,imageSizeConfigSection,paginationConfigSection'],
            'openCompanyGalleryTarget' => ['nullable', 'in:companyGalleryCodeBlock,companyGalleryLibraryBlock,rightSidebarImageConfig,fullScreenSlideConfig'],
            'apiRefreshInterval' => ['nullable', 'integer', 'min:5', 'max:3600'],
            'openRightSidebarImageScheduleUrl' => ['nullable', 'string', 'max:1000'],
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
            'rightSidebarLogoPositionWindows' => ['nullable', 'in:sidebar_top,screen_right_vertical'],
            'rightSidebarLogoPositionAndroid' => ['nullable', 'in:sidebar_top,screen_right_vertical'],
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
            'fullScreenSlideImageUrls' => ['nullable', 'string', 'max:10000'],
            'fullScreenSlideInterval' => ['nullable', 'integer', 'min:1', 'max:300'],
            'fullScreenSlideReturnDelaySeconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'fullScreenSlideEnabled' => ['nullable', 'boolean'],
            'fullScreenSlideStartDate' => ['nullable', 'date_format:Y-m-d'],
            'fullScreenSlideEndDate' => ['nullable', 'date_format:Y-m-d'],
            'fullScreenSlideEnabledWindows' => ['nullable', 'boolean'],
            'fullScreenSlideEnabledAndroid' => ['nullable', 'boolean'],
            'fullScreenSlideImageWidthWindows' => ['nullable', 'integer', 'min:0', 'max:3840'],
            'fullScreenSlideImageHeightWindows' => ['nullable', 'integer', 'min:0', 'max:2160'],
            'fullScreenSlideImageWidthAndroid' => ['nullable', 'integer', 'min:0', 'max:3840'],
            'fullScreenSlideImageHeightAndroid' => ['nullable', 'integer', 'min:0', 'max:2160'],
            'rightSidebarImageSchedules' => ['nullable', 'array'],
            'rightSidebarImageSchedules.*.url' => ['nullable', 'string', 'max:1000'],
            'rightSidebarImageSchedules.*.name' => ['nullable', 'string', 'max:120'],
            'rightSidebarImageSchedules.*.startDate' => ['nullable', 'date_format:Y-m-d'],
            'rightSidebarImageSchedules.*.endDate' => ['nullable', 'date_format:Y-m-d'],
            'rightSidebarImageSchedules.*.imageHeight' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarImageSchedules.*.imageWidth' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarImageSchedules.*.verticalOffset' => ['nullable', 'integer', 'min:-300', 'max:300'],
            'rightSidebarImageSchedules.*.enabledForWindows' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.enabledForAndroid' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.windowsImageHeight' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarImageSchedules.*.windowsImageWidth' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarImageSchedules.*.windowsVerticalOffset' => ['nullable', 'integer', 'min:-300', 'max:300'],
            'rightSidebarImageSchedules.*.windowsImageFit' => ['nullable', 'in:contain,cover,scale-down'],
            'rightSidebarImageSchedules.*.windowsImageBackgroundColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.androidImageHeight' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarImageSchedules.*.androidImageWidth' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'rightSidebarImageSchedules.*.androidVerticalOffset' => ['nullable', 'integer', 'min:-300', 'max:300'],
            'rightSidebarImageSchedules.*.androidImageFit' => ['nullable', 'in:contain,cover,scale-down'],
            'rightSidebarImageSchedules.*.androidImageBackgroundColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.windowsShowName' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.windowsShowPrice' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.windowsPriceText' => ['nullable', 'string', 'max:60'],
            'rightSidebarImageSchedules.*.windowsNameFontSize' => ['nullable', 'integer', 'min:8', 'max:120'],
            'rightSidebarImageSchedules.*.windowsPriceFontSize' => ['nullable', 'integer', 'min:8', 'max:120'],
            'rightSidebarImageSchedules.*.windowsTextFontFamily' => ['nullable', 'in:arial,verdana,tahoma,trebuchet,georgia,courier,system'],
            'rightSidebarImageSchedules.*.windowsNamePosition' => ['nullable', 'in:top,bottom'],
            'rightSidebarImageSchedules.*.windowsPricePosition' => ['nullable', 'in:top,bottom'],
            'rightSidebarImageSchedules.*.windowsNameColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.windowsNameBadgeEnabled' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.windowsNameBadgeColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.windowsPriceColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.windowsPriceBadgeEnabled' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.windowsPriceBadgeColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.androidShowName' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.androidShowPrice' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.androidPriceText' => ['nullable', 'string', 'max:60'],
            'rightSidebarImageSchedules.*.androidNameFontSize' => ['nullable', 'integer', 'min:8', 'max:120'],
            'rightSidebarImageSchedules.*.androidPriceFontSize' => ['nullable', 'integer', 'min:8', 'max:120'],
            'rightSidebarImageSchedules.*.androidTextFontFamily' => ['nullable', 'in:arial,verdana,tahoma,trebuchet,georgia,courier,system'],
            'rightSidebarImageSchedules.*.androidNamePosition' => ['nullable', 'in:top,bottom'],
            'rightSidebarImageSchedules.*.androidPricePosition' => ['nullable', 'in:top,bottom'],
            'rightSidebarImageSchedules.*.androidNameColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.androidNameBadgeEnabled' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.androidNameBadgeColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.androidPriceColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarImageSchedules.*.androidPriceBadgeEnabled' => ['nullable', 'boolean'],
            'rightSidebarImageSchedules.*.androidPriceBadgeColor' => ['nullable', 'string', 'max:9'],
            'suggestedSlideImageSources' => ['nullable', 'array'],
            'suggestedSlideImageSources.*' => ['string', 'regex:/^(slot_[1-3]|company_upload|company_existing_\d+)$/'],
            'suggestedSlideSelectionSubmitted' => ['nullable', 'boolean'],
            'companyGalleryUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'companyGalleryDirectUrl' => ['nullable', 'string', 'max:1000'],
            'rightSidebarLogoUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'rightSidebarLogoUrl' => ['nullable', 'string', 'max:1000'],
            'rightSidebarLogoWidth' => ['nullable', 'integer', 'min:60', 'max:1200'],
            'rightSidebarLogoHeight' => ['nullable', 'integer', 'min:30', 'max:300'],
            'rightSidebarLogoWidthWindows' => ['nullable', 'integer', 'min:60', 'max:1200'],
            'rightSidebarLogoHeightWindows' => ['nullable', 'integer', 'min:30', 'max:300'],
            'rightSidebarLogoWidthAndroid' => ['nullable', 'integer', 'min:60', 'max:1200'],
            'rightSidebarLogoHeightAndroid' => ['nullable', 'integer', 'min:30', 'max:300'],
            'leftVerticalLogoUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'leftVerticalLogoUrl' => ['nullable', 'string', 'max:1000'],
            'leftVerticalLogoWidth' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'leftVerticalLogoHeight' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'leftVerticalLogoWidthWindows' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'leftVerticalLogoHeightWindows' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'leftVerticalLogoWidthAndroid' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'leftVerticalLogoHeightAndroid' => ['nullable', 'integer', 'min:40', 'max:1000'],
            'rightSidebarLogoBackgroundColor' => ['nullable', 'string', 'max:9'],
            'isRightSidebarLogoBackgroundTransparent' => ['nullable', 'boolean'],
            'rightSidebarImageInterval' => ['nullable', 'integer', 'min:1', 'max:300'],
            'rightSidebarImageFit' => ['required', 'in:contain,cover,scale-down'],
            'rightSidebarHybridVideoDuration' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rightSidebarHybridImageDuration' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rightSidebarProductCarouselEnabled' => ['nullable', 'boolean'],
            'rightSidebarProductDisplayMode' => ['nullable', 'in:all,offers_only'],
            'rightSidebarProductTransitionMode' => ['nullable', 'in:products_only,before_images,mixed_with_images,mixed_with_media'],
            'rightSidebarProductInterval' => ['nullable', 'integer', 'min:1', 'max:300'],
            'rightSidebarProductShowImage' => ['nullable', 'boolean'],
            'rightSidebarProductShowName' => ['nullable', 'boolean'],
            'rightSidebarProductShowPrice' => ['nullable', 'boolean'],
            'rightSidebarProductNamePosition' => ['nullable', 'in:top,bottom'],
            'rightSidebarProductPricePosition' => ['nullable', 'in:top,bottom'],
            'rightSidebarProductNameColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarProductPriceColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarProductNameBadgeEnabled' => ['nullable', 'boolean'],
            'rightSidebarProductNameBadgeColor' => ['nullable', 'string', 'max:9'],
            'rightSidebarProductPriceBadgeEnabled' => ['nullable', 'boolean'],
            'rightSidebarProductPriceBadgeColor' => ['nullable', 'string', 'max:9'],
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
            'rowLineSpacing' => ['nullable', 'integer', 'min:0', 'max:40'],
            'productListVerticalOffset' => ['nullable', 'integer', 'min:-300', 'max:300'],
            'listFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelVerticalOffset' => ['nullable', 'integer', 'min:-300', 'max:300'],
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
        $validated['rightSidebarLogoPositionWindows'] = (string) ($validated['rightSidebarLogoPositionWindows'] ?? $validated['rightSidebarLogoPosition'] ?? 'sidebar_top');
        $validated['rightSidebarLogoPositionAndroid'] = (string) ($validated['rightSidebarLogoPositionAndroid'] ?? $validated['rightSidebarLogoPositionWindows'] ?? 'sidebar_top');
        // Keep legacy field aligned with Windows option.
        $validated['rightSidebarLogoPosition'] = $validated['rightSidebarLogoPositionWindows'];
        $validated['showLeftVerticalLogo'] = (bool) ($validated['showLeftVerticalLogo'] ?? false);
        $validated['rightSidebarLogoUrl'] = trim((string) ($validated['rightSidebarLogoUrl'] ?? ''));
        $validated['rightSidebarLogoWidthWindows'] = (int) ($validated['rightSidebarLogoWidthWindows'] ?? $validated['rightSidebarLogoWidth'] ?? 220);
        $validated['rightSidebarLogoHeightWindows'] = (int) ($validated['rightSidebarLogoHeightWindows'] ?? $validated['rightSidebarLogoHeight'] ?? 58);
        $validated['rightSidebarLogoWidthAndroid'] = (int) ($validated['rightSidebarLogoWidthAndroid'] ?? $validated['rightSidebarLogoWidthWindows'] ?? 220);
        $validated['rightSidebarLogoHeightAndroid'] = (int) ($validated['rightSidebarLogoHeightAndroid'] ?? $validated['rightSidebarLogoHeightWindows'] ?? 58);
        // Legacy fields stay mapped to Windows values.
        $validated['rightSidebarLogoWidth'] = $validated['rightSidebarLogoWidthWindows'];
        $validated['rightSidebarLogoHeight'] = $validated['rightSidebarLogoHeightWindows'];
        $validated['leftVerticalLogoUrl'] = trim((string) ($validated['leftVerticalLogoUrl'] ?? ''));
        $validated['leftVerticalLogoWidthWindows'] = (int) ($validated['leftVerticalLogoWidthWindows'] ?? $validated['leftVerticalLogoWidth'] ?? 120);
        $validated['leftVerticalLogoHeightWindows'] = (int) ($validated['leftVerticalLogoHeightWindows'] ?? $validated['leftVerticalLogoHeight'] ?? 220);
        $validated['leftVerticalLogoWidthAndroid'] = (int) ($validated['leftVerticalLogoWidthAndroid'] ?? $validated['leftVerticalLogoWidthWindows'] ?? 120);
        $validated['leftVerticalLogoHeightAndroid'] = (int) ($validated['leftVerticalLogoHeightAndroid'] ?? $validated['leftVerticalLogoHeightWindows'] ?? 220);
        // Legacy fields stay mapped to Windows values.
        $validated['leftVerticalLogoWidth'] = $validated['leftVerticalLogoWidthWindows'];
        $validated['leftVerticalLogoHeight'] = $validated['leftVerticalLogoHeightWindows'];
        $validated['rightSidebarLogoBackgroundColor'] = (string) ($validated['rightSidebarLogoBackgroundColor'] ?? '#0f172a');
        $validated['isRightSidebarLogoBackgroundTransparent'] = (bool) ($validated['isRightSidebarLogoBackgroundTransparent'] ?? false);
        $validated['isMainBorderEnabled'] = (bool) ($validated['isMainBorderEnabled'] ?? false);
        $validated['isRoundedCornersEnabled'] = (bool) ($validated['isRoundedCornersEnabled'] ?? true);
        $validated['showRightSidebarBorder'] = (bool) ($validated['showRightSidebarBorder'] ?? true);
        $validated['videoMuted'] = (bool) ($validated['videoMuted'] ?? false);
        $validated['apiRefreshInterval'] = (int) ($validated['apiRefreshInterval'] ?? 30);
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
        $validated['fullScreenSlideImageUrls'] = $this->normalizeImageUrlsList((string) ($validated['fullScreenSlideImageUrls'] ?? ''));
        $validated['fullScreenSlideInterval'] = (int) ($validated['fullScreenSlideInterval'] ?? 8);
        $validated['fullScreenSlideReturnDelaySeconds'] = (int) ($validated['fullScreenSlideReturnDelaySeconds'] ?? 0);
        $validated['fullScreenSlideEnabled'] = (bool) ($validated['fullScreenSlideEnabled'] ?? false);
        $validated['fullScreenSlideStartDate'] = trim((string) ($validated['fullScreenSlideStartDate'] ?? '')) ?: null;
        $validated['fullScreenSlideEndDate'] = trim((string) ($validated['fullScreenSlideEndDate'] ?? '')) ?: null;
        if (!empty($validated['fullScreenSlideStartDate']) && !empty($validated['fullScreenSlideEndDate']) && $validated['fullScreenSlideEndDate'] < $validated['fullScreenSlideStartDate']) {
            $validated['fullScreenSlideEndDate'] = $validated['fullScreenSlideStartDate'];
        }
        $validated['fullScreenSlideEnabledWindows'] = (bool) ($validated['fullScreenSlideEnabledWindows'] ?? true);
        $validated['fullScreenSlideEnabledAndroid'] = (bool) ($validated['fullScreenSlideEnabledAndroid'] ?? true);
        $validated['fullScreenSlideImageWidthWindows'] = max(0, min(3840, (int) ($validated['fullScreenSlideImageWidthWindows'] ?? 0)));
        $validated['fullScreenSlideImageHeightWindows'] = max(0, min(2160, (int) ($validated['fullScreenSlideImageHeightWindows'] ?? 0)));
        $validated['fullScreenSlideImageWidthAndroid'] = max(0, min(3840, (int) ($validated['fullScreenSlideImageWidthAndroid'] ?? 0)));
        $validated['fullScreenSlideImageHeightAndroid'] = max(0, min(2160, (int) ($validated['fullScreenSlideImageHeightAndroid'] ?? 0)));
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

                $legacyImageHeight = max(0, min(1000, (int) ($item['imageHeight'] ?? 0)));
                $legacyImageWidth = max(0, min(1000, (int) ($item['imageWidth'] ?? 0)));
                $legacyVerticalOffset = max(-300, min(300, (int) ($item['verticalOffset'] ?? 0)));

                $windowsImageHeight = max(0, min(1000, (int) ($item['windowsImageHeight'] ?? $legacyImageHeight)));
                $windowsImageWidth = max(0, min(1000, (int) ($item['windowsImageWidth'] ?? $legacyImageWidth)));
                $windowsVerticalOffset = max(-300, min(300, (int) ($item['windowsVerticalOffset'] ?? $legacyVerticalOffset)));
                $windowsImageFit = (string) ($item['windowsImageFit'] ?? 'scale-down');
                $windowsImageBackgroundColor = $this->normalizeHexColor((string) ($item['windowsImageBackgroundColor'] ?? '#000000'), '#000000');

                $androidImageHeight = max(0, min(1000, (int) ($item['androidImageHeight'] ?? $legacyImageHeight)));
                $androidImageWidth = max(0, min(1000, (int) ($item['androidImageWidth'] ?? $legacyImageWidth)));
                $androidVerticalOffset = max(-300, min(300, (int) ($item['androidVerticalOffset'] ?? $legacyVerticalOffset)));
                $androidImageFit = (string) ($item['androidImageFit'] ?? $windowsImageFit);
                $androidImageBackgroundColor = $this->normalizeHexColor((string) ($item['androidImageBackgroundColor'] ?? $windowsImageBackgroundColor), $windowsImageBackgroundColor);

                $windowsShowName = isset($item['windowsShowName']) ? (bool) $item['windowsShowName'] : false;
                $windowsShowPrice = isset($item['windowsShowPrice']) ? (bool) $item['windowsShowPrice'] : false;
                $windowsPriceText = substr(trim((string) ($item['windowsPriceText'] ?? '')), 0, 60);
                $windowsNameFontSize = max(8, min(120, (int) ($item['windowsNameFontSize'] ?? 18)));
                $windowsPriceFontSize = max(8, min(120, (int) ($item['windowsPriceFontSize'] ?? 22)));
                $windowsTextFontFamily = (string) ($item['windowsTextFontFamily'] ?? 'arial');
                $windowsNamePosition = (string) ($item['windowsNamePosition'] ?? 'top');
                $windowsPricePosition = (string) ($item['windowsPricePosition'] ?? 'bottom');
                $windowsNameColor = $this->normalizeHexColor((string) ($item['windowsNameColor'] ?? '#ffffff'), '#ffffff');
                $windowsNameBadgeEnabled = isset($item['windowsNameBadgeEnabled']) ? (bool) $item['windowsNameBadgeEnabled'] : true;
                $windowsNameBadgeColor = $this->normalizeHexColor((string) ($item['windowsNameBadgeColor'] ?? '#0f172a'), '#0f172a');
                $windowsPriceColor = $this->normalizeHexColor((string) ($item['windowsPriceColor'] ?? '#fde68a'), '#fde68a');
                $windowsPriceBadgeEnabled = isset($item['windowsPriceBadgeEnabled']) ? (bool) $item['windowsPriceBadgeEnabled'] : true;
                $windowsPriceBadgeColor = $this->normalizeHexColor((string) ($item['windowsPriceBadgeColor'] ?? '#0f172a'), '#0f172a');

                $androidShowName = isset($item['androidShowName']) ? (bool) $item['androidShowName'] : false;
                $androidShowPrice = isset($item['androidShowPrice']) ? (bool) $item['androidShowPrice'] : false;
                $androidPriceText = substr(trim((string) ($item['androidPriceText'] ?? '')), 0, 60);
                $androidNameFontSize = max(8, min(120, (int) ($item['androidNameFontSize'] ?? 18)));
                $androidPriceFontSize = max(8, min(120, (int) ($item['androidPriceFontSize'] ?? 22)));
                $androidTextFontFamily = (string) ($item['androidTextFontFamily'] ?? 'arial');
                $androidNamePosition = (string) ($item['androidNamePosition'] ?? 'top');
                $androidPricePosition = (string) ($item['androidPricePosition'] ?? 'bottom');
                $androidNameColor = $this->normalizeHexColor((string) ($item['androidNameColor'] ?? '#ffffff'), '#ffffff');
                $androidNameBadgeEnabled = isset($item['androidNameBadgeEnabled']) ? (bool) $item['androidNameBadgeEnabled'] : true;
                $androidNameBadgeColor = $this->normalizeHexColor((string) ($item['androidNameBadgeColor'] ?? '#0f172a'), '#0f172a');
                $androidPriceColor = $this->normalizeHexColor((string) ($item['androidPriceColor'] ?? '#fde68a'), '#fde68a');
                $androidPriceBadgeEnabled = isset($item['androidPriceBadgeEnabled']) ? (bool) $item['androidPriceBadgeEnabled'] : true;
                $androidPriceBadgeColor = $this->normalizeHexColor((string) ($item['androidPriceBadgeColor'] ?? '#0f172a'), '#0f172a');

                return [
                    'url' => $normalizedUrl,
                    'name' => substr(trim((string) ($item['name'] ?? '')), 0, 120),
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'imageHeight' => $legacyImageHeight,
                    'imageWidth' => $legacyImageWidth,
                    'verticalOffset' => $legacyVerticalOffset,
                    'enabledForWindows' => isset($item['enabledForWindows']) ? (bool) $item['enabledForWindows'] : true,
                    'enabledForAndroid' => isset($item['enabledForAndroid']) ? (bool) $item['enabledForAndroid'] : true,
                    'windowsImageHeight' => $windowsImageHeight,
                    'windowsImageWidth' => $windowsImageWidth,
                    'windowsVerticalOffset' => $windowsVerticalOffset,
                    'windowsImageFit' => in_array($windowsImageFit, ['contain', 'cover', 'scale-down'], true) ? $windowsImageFit : 'scale-down',
                    'windowsImageBackgroundColor' => $windowsImageBackgroundColor,
                    'androidImageHeight' => $androidImageHeight,
                    'androidImageWidth' => $androidImageWidth,
                    'androidVerticalOffset' => $androidVerticalOffset,
                    'androidImageFit' => in_array($androidImageFit, ['contain', 'cover', 'scale-down'], true) ? $androidImageFit : 'scale-down',
                    'androidImageBackgroundColor' => $androidImageBackgroundColor,
                    'windowsShowName' => $windowsShowName,
                    'windowsShowPrice' => $windowsShowPrice,
                    'windowsPriceText' => $windowsPriceText,
                    'windowsNameFontSize' => $windowsNameFontSize,
                    'windowsPriceFontSize' => $windowsPriceFontSize,
                    'windowsTextFontFamily' => in_array($windowsTextFontFamily, ['arial', 'verdana', 'tahoma', 'trebuchet', 'georgia', 'courier', 'system'], true) ? $windowsTextFontFamily : 'arial',
                    'windowsNamePosition' => in_array($windowsNamePosition, ['top', 'bottom'], true) ? $windowsNamePosition : 'top',
                    'windowsPricePosition' => in_array($windowsPricePosition, ['top', 'bottom'], true) ? $windowsPricePosition : 'bottom',
                    'windowsNameColor' => $windowsNameColor,
                    'windowsNameBadgeEnabled' => $windowsNameBadgeEnabled,
                    'windowsNameBadgeColor' => $windowsNameBadgeColor,
                    'windowsPriceColor' => $windowsPriceColor,
                    'windowsPriceBadgeEnabled' => $windowsPriceBadgeEnabled,
                    'windowsPriceBadgeColor' => $windowsPriceBadgeColor,
                    'androidShowName' => $androidShowName,
                    'androidShowPrice' => $androidShowPrice,
                    'androidPriceText' => $androidPriceText,
                    'androidNameFontSize' => $androidNameFontSize,
                    'androidPriceFontSize' => $androidPriceFontSize,
                    'androidTextFontFamily' => in_array($androidTextFontFamily, ['arial', 'verdana', 'tahoma', 'trebuchet', 'georgia', 'courier', 'system'], true) ? $androidTextFontFamily : 'arial',
                    'androidNamePosition' => in_array($androidNamePosition, ['top', 'bottom'], true) ? $androidNamePosition : 'top',
                    'androidPricePosition' => in_array($androidPricePosition, ['top', 'bottom'], true) ? $androidPricePosition : 'bottom',
                    'androidNameColor' => $androidNameColor,
                    'androidNameBadgeEnabled' => $androidNameBadgeEnabled,
                    'androidNameBadgeColor' => $androidNameBadgeColor,
                    'androidPriceColor' => $androidPriceColor,
                    'androidPriceBadgeEnabled' => $androidPriceBadgeEnabled,
                    'androidPriceBadgeColor' => $androidPriceBadgeColor,
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
        $validated['rightSidebarProductCarouselEnabled'] = (bool) ($validated['rightSidebarProductCarouselEnabled'] ?? false);
        $validated['rightSidebarProductDisplayMode'] = (string) ($validated['rightSidebarProductDisplayMode'] ?? 'all');
        $validated['rightSidebarProductTransitionMode'] = (string) ($validated['rightSidebarProductTransitionMode'] ?? 'products_only');
        $validated['rightSidebarProductInterval'] = (int) ($validated['rightSidebarProductInterval'] ?? 8);
        $validated['rightSidebarProductShowImage'] = (bool) ($validated['rightSidebarProductShowImage'] ?? true);
        $validated['rightSidebarProductShowName'] = (bool) ($validated['rightSidebarProductShowName'] ?? true);
        $validated['rightSidebarProductShowPrice'] = (bool) ($validated['rightSidebarProductShowPrice'] ?? true);
        $validated['rightSidebarProductNamePosition'] = (string) ($validated['rightSidebarProductNamePosition'] ?? 'top');
        $validated['rightSidebarProductPricePosition'] = (string) ($validated['rightSidebarProductPricePosition'] ?? 'bottom');
        $validated['rightSidebarProductNameColor'] = $this->normalizeHexColor((string) ($validated['rightSidebarProductNameColor'] ?? '#ffffff'), '#ffffff');
        $validated['rightSidebarProductPriceColor'] = $this->normalizeHexColor((string) ($validated['rightSidebarProductPriceColor'] ?? '#fde68a'), '#fde68a');
        $validated['rightSidebarProductNameBadgeEnabled'] = (bool) ($validated['rightSidebarProductNameBadgeEnabled'] ?? true);
        $validated['rightSidebarProductNameBadgeColor'] = $this->normalizeHexColor((string) ($validated['rightSidebarProductNameBadgeColor'] ?? '#0f172a'), '#0f172a');
        $validated['rightSidebarProductPriceBadgeEnabled'] = (bool) ($validated['rightSidebarProductPriceBadgeEnabled'] ?? true);
        $validated['rightSidebarProductPriceBadgeColor'] = $this->normalizeHexColor((string) ($validated['rightSidebarProductPriceBadgeColor'] ?? '#0f172a'), '#0f172a');
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
        $validated['rowLineSpacing'] = (int) ($validated['rowLineSpacing'] ?? 12);
        $validated['productListVerticalOffset'] = (int) ($validated['productListVerticalOffset'] ?? 0);
        $validated['listFontSize'] = (int) ($validated['listFontSize'] ?? 16);
        $validated['groupLabelFontSize'] = (int) ($validated['groupLabelFontSize'] ?? 14);
        $validated['groupLabelVerticalOffset'] = (int) ($validated['groupLabelVerticalOffset'] ?? 0);
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
        unset($validated['openCompanyGalleryTarget']);
        unset($validated['openRightSidebarImageScheduleUrl']);

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

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoPositionWindows')) {
            unset($validated['rightSidebarLogoPositionWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoPositionAndroid')) {
            unset($validated['rightSidebarLogoPositionAndroid']);
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

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoWidthWindows')) {
            unset($validated['rightSidebarLogoWidthWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoHeightWindows')) {
            unset($validated['rightSidebarLogoHeightWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoWidthAndroid')) {
            unset($validated['rightSidebarLogoWidthAndroid']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarLogoHeightAndroid')) {
            unset($validated['rightSidebarLogoHeightAndroid']);
        }

        if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoWidthWindows')) {
            unset($validated['leftVerticalLogoWidthWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoHeightWindows')) {
            unset($validated['leftVerticalLogoHeightWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoWidthAndroid')) {
            unset($validated['leftVerticalLogoWidthAndroid']);
        }

        if (! Schema::hasColumn('configuracoes', 'leftVerticalLogoHeightAndroid')) {
            unset($validated['leftVerticalLogoHeightAndroid']);
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
            $scheduleSubmittedUrls = collect((array) ($validated['rightSidebarImageSchedules'] ?? []))
                ->map(fn (array $item) => $this->normalizeSingleImageUrl((string) ($item['url'] ?? '')))
                ->filter(fn (string $url) => $url !== '')
                ->values()
                ->all();

            $manualSlideUrls = collect(preg_split('/\r?\n/', (string) ($validated['rightSidebarImageUrls'] ?? '')) ?: [])
                ->map(fn (string $line) => trim($line))
                ->filter(fn (string $line) => $line !== '')
                ->map(fn (string $line) => $this->normalizeSingleImageUrl($line))
                ->filter(fn (string $line) => $line !== '')
                ->merge($scheduleSubmittedUrls)
                ->unique()
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
                if ($companyUploadUrl !== '' && !in_array($companyUploadUrl, $manualSlideUrls, true)) {
                    $manualSlideUrls[] = $companyUploadUrl;
                }
            }

            $companyDirectUrl = $this->normalizeSingleImageUrl((string) ($validated['companyGalleryDirectUrl'] ?? ''));
            if ($companyDirectUrl !== '' && !in_array($companyDirectUrl, $manualSlideUrls, true)) {
                $manualSlideUrls[] = $companyDirectUrl;
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

            if (empty($finalSlideUrls) && !empty($availableGalleryImages) && !$slideSelectionSubmitted) {
                $finalSlideUrls = array_values($availableGalleryImages);
            }

            // Keep list and editor in sync: any URL present in schedule payload must
            // also remain in the slide URLs list shown in the UI.
            $finalSlideUrls = array_values(array_unique(array_merge($finalSlideUrls, $scheduleSubmittedUrls)));

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

            // When the slide section is saved with images, keep media mode aligned so
            // the right sidebar actually renders the configured slide.
            if (
                $saveSection === 'companyGalleryConfigSection'
                && ! empty($finalSlideUrlSet)
                && ($validated['rightSidebarProductCarouselEnabled'] ?? false) === false
                && (string) ($validated['rightSidebarMediaType'] ?? 'video') === 'video'
            ) {
                $validated['rightSidebarMediaType'] = 'image';
            }

        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarImageSchedules')) {
            unset($validated['rightSidebarImageSchedules']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideImageUrls')) {
            unset($validated['fullScreenSlideImageUrls']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideInterval')) {
            unset($validated['fullScreenSlideInterval']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideReturnDelaySeconds')) {
            unset($validated['fullScreenSlideReturnDelaySeconds']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideEnabled')) {
            unset($validated['fullScreenSlideEnabled']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideStartDate')) {
            unset($validated['fullScreenSlideStartDate']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideEndDate')) {
            unset($validated['fullScreenSlideEndDate']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideEnabledWindows')) {
            unset($validated['fullScreenSlideEnabledWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideEnabledAndroid')) {
            unset($validated['fullScreenSlideEnabledAndroid']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideImageWidthWindows')) {
            unset($validated['fullScreenSlideImageWidthWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideImageHeightWindows')) {
            unset($validated['fullScreenSlideImageHeightWindows']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideImageWidthAndroid')) {
            unset($validated['fullScreenSlideImageWidthAndroid']);
        }

        if (! Schema::hasColumn('configuracoes', 'fullScreenSlideImageHeightAndroid')) {
            unset($validated['fullScreenSlideImageHeightAndroid']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductCarouselEnabled')) {
            unset($validated['rightSidebarProductCarouselEnabled']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductDisplayMode')) {
            unset($validated['rightSidebarProductDisplayMode']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductTransitionMode')) {
            unset($validated['rightSidebarProductTransitionMode']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductInterval')) {
            unset($validated['rightSidebarProductInterval']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductShowImage')) {
            unset($validated['rightSidebarProductShowImage']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductShowName')) {
            unset($validated['rightSidebarProductShowName']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductShowPrice')) {
            unset($validated['rightSidebarProductShowPrice']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductNamePosition')) {
            unset($validated['rightSidebarProductNamePosition']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductPricePosition')) {
            unset($validated['rightSidebarProductPricePosition']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductNameColor')) {
            unset($validated['rightSidebarProductNameColor']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductPriceColor')) {
            unset($validated['rightSidebarProductPriceColor']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductNameBadgeEnabled')) {
            unset($validated['rightSidebarProductNameBadgeEnabled']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductNameBadgeColor')) {
            unset($validated['rightSidebarProductNameBadgeColor']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductPriceBadgeEnabled')) {
            unset($validated['rightSidebarProductPriceBadgeEnabled']);
        }

        if (! Schema::hasColumn('configuracoes', 'rightSidebarProductPriceBadgeColor')) {
            unset($validated['rightSidebarProductPriceBadgeColor']);
        }

        if (! Schema::hasColumn('configuracoes', 'productListVerticalOffset')) {
            unset($validated['productListVerticalOffset']);
        }

        if (! Schema::hasColumn('configuracoes', 'groupLabelVerticalOffset')) {
            unset($validated['groupLabelVerticalOffset']);
        }

        if ($validated['rightSidebarMediaType'] === 'video') {
            $validated['rightSidebarImageUrls'] = null;
        }

        unset($validated['suggestedSlideImageSources'], $validated['suggestedSlideSelectionSubmitted'], $validated['companyGalleryUpload'], $validated['companyGalleryDirectUrl'], $validated['rightSidebarLogoUpload'], $validated['leftVerticalLogoUpload']);

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
            $redirect->with('openConfigSection', $saveSection);

            if ($saveSection === 'companyGalleryConfigSection') {
                $openCompanyGalleryTarget = trim((string) $request->input('openCompanyGalleryTarget', ''));
                if (in_array($openCompanyGalleryTarget, ['companyGalleryCodeBlock', 'companyGalleryLibraryBlock', 'rightSidebarImageConfig', 'fullScreenSlideConfig'], true)) {
                    $redirect->with('openCompanyGalleryTarget', $openCompanyGalleryTarget);
                }

                $openRightSidebarImageScheduleUrl = $this->normalizeSingleImageUrl((string) $request->input('openRightSidebarImageScheduleUrl', ''));
                if ($openRightSidebarImageScheduleUrl !== '') {
                    $redirect->with('openRightSidebarImageScheduleUrl', $openRightSidebarImageScheduleUrl);
                }
            }
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
            'apiRefreshInterval',
            'showVideoPanel',
            'showRightSidebarPanel',
            'showRightSidebarLogo',
            'rightSidebarLogoPosition',
            'rightSidebarLogoPositionWindows',
            'rightSidebarLogoPositionAndroid',
            'showLeftVerticalLogo',
            'rightSidebarLogoUrl',
            'rightSidebarLogoWidth',
            'rightSidebarLogoHeight',
            'rightSidebarLogoWidthWindows',
            'rightSidebarLogoHeightWindows',
            'rightSidebarLogoWidthAndroid',
            'rightSidebarLogoHeightAndroid',
            'leftVerticalLogoUrl',
            'leftVerticalLogoWidth',
            'leftVerticalLogoHeight',
            'leftVerticalLogoWidthWindows',
            'leftVerticalLogoHeightWindows',
            'leftVerticalLogoWidthAndroid',
            'leftVerticalLogoHeightAndroid',
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
            'fullScreenSlideImageUrls',
            'fullScreenSlideInterval',
            'fullScreenSlideReturnDelaySeconds',
            'fullScreenSlideEnabled',
            'fullScreenSlideStartDate',
            'fullScreenSlideEndDate',
            'fullScreenSlideEnabledWindows',
            'fullScreenSlideEnabledAndroid',
            'fullScreenSlideImageWidthWindows',
            'fullScreenSlideImageHeightWindows',
            'fullScreenSlideImageWidthAndroid',
            'fullScreenSlideImageHeightAndroid',
            'rightSidebarImageInterval',
            'rightSidebarImageFit',
            'rightSidebarImageHeight',
            'rightSidebarImageWidth',
            'rightSidebarAndroidHeight',
            'rightSidebarAndroidWidth',
            'rightSidebarAndroidVerticalOffset',
            'rightSidebarHybridVideoDuration',
            'rightSidebarHybridImageDuration',
            'rightSidebarProductCarouselEnabled',
            'rightSidebarProductDisplayMode',
            'rightSidebarProductTransitionMode',
            'rightSidebarProductInterval',
            'rightSidebarProductShowImage',
            'rightSidebarProductShowName',
            'rightSidebarProductShowPrice',
            'rightSidebarProductNamePosition',
            'rightSidebarProductPricePosition',
            'rightSidebarProductNameColor',
            'rightSidebarProductPriceColor',
            'rightSidebarProductNameBadgeEnabled',
            'rightSidebarProductNameBadgeColor',
            'rightSidebarProductPriceBadgeEnabled',
            'rightSidebarProductPriceBadgeColor',
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
            'rowLineSpacing',
            'productListVerticalOffset',
            'listFontSize',
            'groupLabelFontSize',
            'groupLabelVerticalOffset',
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

    private function normalizeHexColor(string $raw, string $default): string
    {
        $value = strtoupper(trim($raw));

        if (preg_match('/^#[0-9A-F]{6}$/', $value) === 1) {
            return $value;
        }

        return strtoupper($default);
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

        return (int) EmpresaContext::requireEmpresaId($user);
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
