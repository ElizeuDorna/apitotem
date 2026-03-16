import { createDedicatedFullScreenSlideModule } from './tv/modules/dedicated-fullscreen-slide';
import { createRightSidebarMediaModeModule } from './tv/modules/right-sidebar-media-mode';
import { createRightSidebarMixedModeModule } from './tv/modules/right-sidebar-mixed-mode';
import { createRightSidebarProductSequencesModule } from './tv/modules/right-sidebar-product-sequences';
import { createRightSidebarProductCardRendererModule } from './tv/modules/right-sidebar-product-card-renderer';

const tokenInput = document.getElementById('deviceToken');
const loadButton = document.getElementById('loadProducts');
const statusMessage = document.getElementById('statusMessage');
const totalProducts = document.getElementById('totalProducts');
const productsGroupLabel = document.getElementById('productsGroupLabel');
const productsGrid = document.getElementById('productsGrid');
const emptyState = document.getElementById('emptyState');
const tvVideo = document.getElementById('tvVideo');
const tvEmbed = document.getElementById('tvEmbed');
const tvImageSlide = document.getElementById('tvImageSlide');
const videoHint = document.getElementById('videoHint');
const tvHeader = document.getElementById('tvHeader');
const tvHeaderTitle = document.getElementById('tvHeaderTitle');
const tvFooter = document.getElementById('tvFooter');
const tvFooterTitle = document.getElementById('tvFooterTitle');
const tvProductsPanel = document.getElementById('tvProductsPanel');
const tvVideoPanel = document.getElementById('tvVideoPanel');
const tvRightSidebarMediaWrap = document.getElementById('tvRightSidebarMediaWrap');
const tvRightSidebarLogoSlot = document.getElementById('tvRightSidebarLogoSlot');
const tvRightSidebarLogo = document.getElementById('tvRightSidebarLogo');
const tvLeftVerticalLogoSlot = document.getElementById('tvLeftVerticalLogoSlot');
const tvLeftVerticalLogo = document.getElementById('tvLeftVerticalLogo');
const tvMain = document.getElementById('tvMain');
const tvShell = document.getElementById('tvShell');
const fullscreenTestButton = document.getElementById('fullscreenTestButton');

const queryParams = new URLSearchParams(window.location.search);
const TOKEN_HISTORY_KEY = 'tv_device_token_history_v1';
const TOKEN_BACKUP_KEY = 'tv_device_token_backup';
const DEDICATED_FULLSCREEN_SKIP_ON_NEXT_LOAD_KEY = 'tv_dedicated_fullscreen_skip_next_load_v1';
const WINDOW_NAME_SKIP_MARKER = '__tv_dedicated_fs_skip_next_load__';
const WINDOW_NAME_COMPLETED_SIGNATURE_PREFIX = '__tv_dedicated_fs_completed_signature=';

function detectReloadNavigation() {
    try {
        const entries = performance.getEntriesByType('navigation');
        if (Array.isArray(entries) && entries.length > 0) {
            return String(entries[0]?.type || '').toLowerCase() === 'reload';
        }

        return performance.navigation?.type === 1;
    } catch (_error) {
        return false;
    }
}

const isPageReloadNavigation = detectReloadNavigation();

function readCompletedSignatureFromWindowName() {
    try {
        const value = String(window.name || '');
        const regex = new RegExp(`${WINDOW_NAME_COMPLETED_SIGNATURE_PREFIX}([^;]*)`);
        const match = value.match(regex);
        if (!match || !match[1]) {
            return '';
        }

        return decodeURIComponent(String(match[1] || ''));
    } catch (_error) {
        return '';
    }
}

function writeCompletedSignatureToWindowName(signature) {
    try {
        const current = String(window.name || '');
        const cleaned = current.replace(new RegExp(`${WINDOW_NAME_COMPLETED_SIGNATURE_PREFIX}[^;]*;?`, 'g'), '');
        const normalized = String(signature || '').trim();
        const compact = cleaned.replace(/;;+/g, ';').replace(/^;|;$/g, '');

        if (!normalized) {
            window.name = compact;
            return;
        }

        const nextMarker = `${WINDOW_NAME_COMPLETED_SIGNATURE_PREFIX}${encodeURIComponent(normalized)}`;
        window.name = compact ? `${compact};${nextMarker}` : nextMarker;
    } catch (_error) {
    }
}

function consumeWindowNameSkipDedicatedFullscreenFlag() {
    try {
        const current = String(window.name || '');
        if (!current.includes(WINDOW_NAME_SKIP_MARKER)) {
            return false;
        }

        window.name = current.replace(WINDOW_NAME_SKIP_MARKER, '').replace(/;;+/g, ';').replace(/^;|;$/g, '');
        return true;
    } catch (_error) {
        return false;
    }
}

function consumeSkipDedicatedFullscreenOnLoadFlag() {
    try {
        const raw = String(sessionStorage.getItem(DEDICATED_FULLSCREEN_SKIP_ON_NEXT_LOAD_KEY) || '').trim();
        sessionStorage.removeItem(DEDICATED_FULLSCREEN_SKIP_ON_NEXT_LOAD_KEY);
        return raw === '1' || consumeWindowNameSkipDedicatedFullscreenFlag();
    } catch (_error) {
        return consumeWindowNameSkipDedicatedFullscreenFlag();
    }
}

const shouldSkipDedicatedFullscreenOnThisLoad = consumeSkipDedicatedFullscreenOnLoadFlag();

function readTokenHistory() {
    try {
        const raw = localStorage.getItem(TOKEN_HISTORY_KEY);
        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map((item) => String(item || '').trim())
            .filter((item) => item !== '');
    } catch (_error) {
        return [];
    }
}

function persistReliableDeviceToken(token) {
    const normalized = String(token || '').trim();
    if (!normalized) {
        return;
    }

    localStorage.setItem('tv_device_token', normalized);
    localStorage.setItem('tv_last_device_token', normalized);
    localStorage.setItem(TOKEN_BACKUP_KEY, normalized);

    try {
        const history = readTokenHistory().filter((item) => item !== normalized);
        history.unshift(normalized);
        localStorage.setItem(TOKEN_HISTORY_KEY, JSON.stringify(history.slice(0, 10)));
    } catch (_error) {
    }
}

function getReliableDeviceToken() {
    const fromQuery = String(queryParams.get('token') || '').trim();
    const fromPrimary = String(localStorage.getItem('tv_device_token') || '').trim();
    const fromLast = String(localStorage.getItem('tv_last_device_token') || '').trim();
    const fromBackup = String(localStorage.getItem(TOKEN_BACKUP_KEY) || '').trim();
    const fromHistory = readTokenHistory()[0] || '';
    const token = fromQuery || fromPrimary || fromLast || fromBackup || fromHistory;

    if (token) {
        persistReliableDeviceToken(token);
    }

    return token;
}

const initialToken = getReliableDeviceToken();
const apiEndpoint = localStorage.getItem('tv_api_endpoint') || queryParams.get('api') || '/api/tv/produtos';
const configEndpoint = '/api/tv/totemweb/config';
const mediaEndpoint = '/api/tv/midias';
const configPageUrl = '/tv/totemweb/configuracao';
const initialRefreshSeconds = Number(localStorage.getItem('tv_refresh_seconds') || queryParams.get('refresh') || 30);
let refreshSeconds = Math.max(5, Math.min(3600, Number(initialRefreshSeconds || 30)));
const AUDIO_UNLOCK_STORAGE_KEY = 'tv_audio_autoplay_unlocked';
const VISUAL_CONFIG_CACHE_KEY = 'tv_cached_visual_config_v1';
const PRODUCTS_CACHE_KEY = 'tv_cached_products_v1';
const FULLSCREEN_SLIDE_COMPLETED_SIGNATURE_KEY = 'tv_fullscreen_slide_completed_signature_v1';

const visualConfig = {
    videoUrl: '',
    videoMuted: false,
    videoPlaylist: [],
    showVideoPanel: true,
    showRightSidebarPanel: true,
    showRightSidebarLogo: false,
    showLeftVerticalLogo: false,
    rightSidebarLogoPosition: 'sidebar_top',
    rightSidebarLogoPositionWindows: 'sidebar_top',
    rightSidebarLogoPositionAndroid: 'sidebar_top',
    rightSidebarLogoUrl: '',
    leftVerticalLogoUrl: '',
    leftVerticalLogoWidth: 120,
    leftVerticalLogoHeight: 220,
    leftVerticalLogoWidthWindows: 120,
    leftVerticalLogoHeightWindows: 220,
    leftVerticalLogoWidthAndroid: 120,
    leftVerticalLogoHeightAndroid: 220,
    rightSidebarLogoWidth: 220,
    rightSidebarLogoHeight: 58,
    rightSidebarLogoWidthWindows: 220,
    rightSidebarLogoHeightWindows: 58,
    rightSidebarLogoWidthAndroid: 220,
    rightSidebarLogoHeightAndroid: 58,
    rightSidebarLogoBackgroundColor: '#0f172a',
    isRightSidebarLogoBackgroundTransparent: false,
    isMainBorderEnabled: false,
    isRoundedCornersEnabled: true,
    isRowRoundedEnabled: false,
    mainBorderColor: '#000000',
    mainBorderWidth: 1,
    appBackgroundColor: '#020617',
    productsPanelBackgroundColor: '#0f172a',
    listBorderColor: '#334155',
    listBorderWidth: 1,
    videoBackgroundColor: '#000000',
    showRightSidebarBorder: true,
    rightSidebarBorderColor: '#334155',
    rightSidebarBorderWidth: 1,
    rightSidebarMediaType: 'video',
    rightSidebarImageUrls: '',
    fullScreenSlideImageUrls: '',
    fullScreenSlideInterval: 8,
    fullScreenSlideReturnDelaySeconds: 0,
    fullScreenSlideEnabled: false,
    fullScreenSlideStartDate: '',
    fullScreenSlideEndDate: '',
    fullScreenSlideEnabledWindows: true,
    fullScreenSlideEnabledAndroid: true,
    fullScreenSlideImageWidthWindows: 0,
    fullScreenSlideImageHeightWindows: 0,
    fullScreenSlideImageWidthAndroid: 0,
    fullScreenSlideImageHeightAndroid: 0,
    rightSidebarImageSchedules: [],
    rightSidebarImageInterval: 8,
    rightSidebarImageFit: 'scale-down',
    rightSidebarAndroidHeight: 0,
    rightSidebarAndroidWidth: 0,
    rightSidebarAndroidVerticalOffset: 0,
    rightSidebarHybridVideoDuration: 120,
    rightSidebarHybridImageDuration: 120,
    rightSidebarProductCarouselEnabled: false,
    rightSidebarProductDisplayMode: 'all',
    rightSidebarProductTransitionMode: 'products_only',
    rightSidebarProductInterval: 8,
    rightSidebarProductShowImage: true,
    rightSidebarProductShowName: true,
    rightSidebarProductShowPrice: true,
    rightSidebarProductNamePosition: 'top',
    rightSidebarProductPricePosition: 'bottom',
    rightSidebarProductNameColor: '#FFFFFF',
    rightSidebarProductPriceColor: '#FDE68A',
    rightSidebarProductNameBadgeEnabled: true,
    rightSidebarProductNameBadgeColor: '#0F172A',
    rightSidebarProductPriceBadgeEnabled: true,
    rightSidebarProductPriceBadgeColor: '#0F172A',
    productListType: '1',
    productListLeftGroupIds: [],
    productListRightGroupIds: [],
    isVideoPanelTransparent: false,
    rowBackgroundColor: '#020617',
    borderColor: '#334155',
    rowBorderWidth: 1,
    isRowBorderTransparent: false,
    priceColor: '#818cf8',
    showBorder: true,
    showTitle: true,
    titleText: 'Lista de Produtos (TV)',
    isTitleDynamic: false,
    titlePosition: 'top',
    titleFontSize: 32,
    titleFontFamily: 'arial',
    titleTextColor: '#f8fafc',
    titleBackgroundColor: '#0f172a',
    isTitleBackgroundTransparent: false,
    showTitleBorder: true,
    showImage: true,
    showBackgroundImage: false,
    isProductsPanelTransparent: false,
    isListBorderTransparent: false,
    backgroundImageUrl: '',
    useGradient: false,
    gradientStartColor: '#111827',
    gradientEndColor: '#1f2937',
    imageWidth: 56,
    imageHeight: 56,
    rowVerticalPadding: 9,
    rowLineSpacing: 12,
    productListVerticalOffset: 0,
    listFontSize: 16,
    groupLabelFontSize: 14,
    groupLabelVerticalOffset: 0,
    groupLabelFontFamily: 'arial',
    groupLabelColor: '#cbd5e1',
    showGroupLabelBadge: false,
    groupLabelBadgeColor: '#0f172a',
    isPaginationEnabled: false,
    pageSize: 10,
    paginationInterval: 5,
};

let paginationTimer = null;
let videoPlaylistItems = [];
let currentVideoIndex = 0;
let youTubeApiLoading = false;
let youTubeApiReady = false;
let youTubePlayer = null;
let youTubeStatePollTimer = null;
let youTubePlayerReady = false;
let youTubePlayKeepAliveTimer = null;
let shouldUnmuteYouTubeAfterStart = false;
let youTubeUnmuteDelayTimer = null;
let youTubeStartupTimeoutTimer = null;
let youTubeRequestedMuted = false;
let youTubeStartupMuteRetryDone = false;
let currentYouTubeVideoId = '';
let currentVideoPlaylistSignature = '';
let lastVideoAdvanceAt = 0;
let lastNonCriticalAuthWarningAt = 0;
let videoFallbackTimer = null;
let initialVideoAutoplayRetryTimer = null;
let imageSlideTimer = null;
let imageSlideUrls = [];
let fullScreenSlideTimer = null;
let fullScreenSlideExitTimer = null;
let fullScreenSlideReturnTimer = null;
let fullScreenSlideUrls = [];
let fullScreenSlideIndex = 0;
let hasCompletedDedicatedFullScreenSlideCycle = false;
let fullScreenSlideConfigSignature = '';
let fullScreenSlideCompletedSignatureMemory = '';
let fullScreenSlideCompletedSignatureRuntime = '';
let dedicatedFullScreenCycleStartCount = 0;
let imageSlideSettingsByUrl = new Map();
let slideNameOverlay = null;
let slidePriceOverlay = null;
let currentImageSlideIndex = 0;
let rightSidebarHybridPhase = 'video';
let rightSidebarHybridVideoCountInPhase = 0;
let rightSidebarHybridImageCountInPhase = 0;
let rightSidebarHybridSwitching = false;
let rightSidebarHybridLastCompletedAt = 0;
let rightSidebarHybridConfigSignature = '';
let forceVideoPlaylistApplyOnce = false;
let rightSidebarHybridHasShownAnyImage = false;
let audioAutoplayUnlocked = localStorage.getItem(AUDIO_UNLOCK_STORAGE_KEY) === '1';
let forceMuteOnFirstPlayback = true;
let autoFullscreenGestureListenerAttached = false;
let autoFullscreenRetryTimer = null;
let fullscreenWarningShown = false;
let compactLayoutRefreshRaf = null;
let offlineIndicatorEl = null;
let sidebarProductTimer = null;
let sidebarProductCardEl = null;
let sidebarProductItems = [];
let sidebarProductIndex = 0;
let sidebarProductPassedBeforeImages = false;
let sidebarMixedImageUrls = [];
let sidebarMixedImageIndex = 0;
let sidebarMixedVideoItems = [];
let sidebarMixedVideoIndex = 0;
let sidebarMixedTurnIndex = 0;
let sidebarMixedModeSignature = '';
let sidebarMixedVideoTurnActive = false;
let sidebarMixedVideoTurnExpiresAt = 0;
let sidebarMixedCurrentVideoItem = null;
let sidebarMixedOnVideoFinished = null;
let forceFullScreenSlideModeActive = false;
let dedicatedFullScreenSlideStartedAt = 0;
let dedicatedFullScreenSlideMaxExpectedMs = 0;
let productsRefreshTimer = null;
let latestProductsForPagination = [];
let dedicatedFullScreenExternalTimersPaused = false;

const dedicatedFullScreenSlideState = {
    get fullScreenSlideTimer() { return fullScreenSlideTimer; },
    set fullScreenSlideTimer(value) { fullScreenSlideTimer = value; },
    get fullScreenSlideExitTimer() { return fullScreenSlideExitTimer; },
    set fullScreenSlideExitTimer(value) { fullScreenSlideExitTimer = value; },
    get fullScreenSlideReturnTimer() { return fullScreenSlideReturnTimer; },
    set fullScreenSlideReturnTimer(value) { fullScreenSlideReturnTimer = value; },
    get fullScreenSlideUrls() { return fullScreenSlideUrls; },
    set fullScreenSlideUrls(value) { fullScreenSlideUrls = value; },
    get fullScreenSlideIndex() { return fullScreenSlideIndex; },
    set fullScreenSlideIndex(value) { fullScreenSlideIndex = value; },
    get hasCompletedDedicatedFullScreenSlideCycle() { return hasCompletedDedicatedFullScreenSlideCycle; },
    set hasCompletedDedicatedFullScreenSlideCycle(value) { hasCompletedDedicatedFullScreenSlideCycle = value; },
    get fullScreenSlideConfigSignature() { return fullScreenSlideConfigSignature; },
    set fullScreenSlideConfigSignature(value) { fullScreenSlideConfigSignature = value; },
    get fullScreenSlideCompletedSignatureMemory() { return fullScreenSlideCompletedSignatureMemory; },
    set fullScreenSlideCompletedSignatureMemory(value) { fullScreenSlideCompletedSignatureMemory = value; },
    get fullScreenSlideCompletedSignatureRuntime() { return fullScreenSlideCompletedSignatureRuntime; },
    set fullScreenSlideCompletedSignatureRuntime(value) { fullScreenSlideCompletedSignatureRuntime = value; },
    get dedicatedFullScreenCycleStartCount() { return dedicatedFullScreenCycleStartCount; },
    set dedicatedFullScreenCycleStartCount(value) { dedicatedFullScreenCycleStartCount = value; },
    get imageSlideUrls() { return imageSlideUrls; },
    set imageSlideUrls(value) { imageSlideUrls = value; },
    get imageSlideSettingsByUrl() { return imageSlideSettingsByUrl; },
    set imageSlideSettingsByUrl(value) { imageSlideSettingsByUrl = value; },
    get forceFullScreenSlideModeActive() { return forceFullScreenSlideModeActive; },
    set forceFullScreenSlideModeActive(value) { forceFullScreenSlideModeActive = value; },
    get dedicatedFullScreenSlideStartedAt() { return dedicatedFullScreenSlideStartedAt; },
    set dedicatedFullScreenSlideStartedAt(value) { dedicatedFullScreenSlideStartedAt = value; },
    get dedicatedFullScreenSlideMaxExpectedMs() { return dedicatedFullScreenSlideMaxExpectedMs; },
    set dedicatedFullScreenSlideMaxExpectedMs(value) { dedicatedFullScreenSlideMaxExpectedMs = value; },
};

const dedicatedFullScreenSlideModule = createDedicatedFullScreenSlideModule({
    state: dedicatedFullScreenSlideState,
    visualConfig,
    tvImageSlide,
    toBoolean,
    isAndroidDevice,
    parseConfiguredUrlLines,
    resolveRenderableImageUrl,
    readCompletedSignatureFromWindowName,
    getStoredCompletedFullscreenSlideSignature,
    storeCompletedFullscreenSlideSignature,
    clearFullScreenSlideTimer,
    clearFullScreenSlideExitTimer,
    clearFullScreenSlideReturnTimer,
    clearImageSlideTimer,
    stopVideoPlaybackForImageMode,
    hideSidebarProductCard,
    hideSlideTextOverlays,
    showImageSlideAt,
    setImageSlideFullscreenMode,
    isVideoFullscreenModeActive,
    shouldKeepRightSidebarVideoRunningDuringDedicatedFullScreen,
    ensureRightSidebarMediaRestoredAfterDedicatedFullScreen,
    forceRestoreMainLayoutAfterDedicatedSlide,
    pauseExternalTimersForDedicatedFullScreen,
    resumeExternalTimersForDedicatedFullScreen,
    getReliableDeviceToken,
    getSidebarProductItems: () => sidebarProductItems,
    getLatestProductsForPagination: () => latestProductsForPagination,
    applyRightSidebarMediaMode,
});

const rightSidebarMediaModeState = {
    get fullScreenSlideConfigSignature() { return fullScreenSlideConfigSignature; },
    set fullScreenSlideConfigSignature(value) { fullScreenSlideConfigSignature = value; },
    get forceFullScreenSlideModeActive() { return forceFullScreenSlideModeActive; },
    set forceFullScreenSlideModeActive(value) { forceFullScreenSlideModeActive = value; },
    get sidebarProductItems() { return sidebarProductItems; },
    set sidebarProductItems(value) { sidebarProductItems = value; },
    get sidebarMixedModeSignature() { return sidebarMixedModeSignature; },
    set sidebarMixedModeSignature(value) { sidebarMixedModeSignature = value; },
    get sidebarProductPassedBeforeImages() { return sidebarProductPassedBeforeImages; },
    set sidebarProductPassedBeforeImages(value) { sidebarProductPassedBeforeImages = value; },
    get sidebarMixedImageUrls() { return sidebarMixedImageUrls; },
    set sidebarMixedImageUrls(value) { sidebarMixedImageUrls = value; },
    get sidebarMixedImageIndex() { return sidebarMixedImageIndex; },
    set sidebarMixedImageIndex(value) { sidebarMixedImageIndex = value; },
    get sidebarMixedVideoItems() { return sidebarMixedVideoItems; },
    set sidebarMixedVideoItems(value) { sidebarMixedVideoItems = value; },
    get sidebarMixedVideoIndex() { return sidebarMixedVideoIndex; },
    set sidebarMixedVideoIndex(value) { sidebarMixedVideoIndex = value; },
    get sidebarMixedTurnIndex() { return sidebarMixedTurnIndex; },
    set sidebarMixedTurnIndex(value) { sidebarMixedTurnIndex = value; },
    get sidebarMixedVideoTurnActive() { return sidebarMixedVideoTurnActive; },
    set sidebarMixedVideoTurnActive(value) { sidebarMixedVideoTurnActive = value; },
    get sidebarMixedVideoTurnExpiresAt() { return sidebarMixedVideoTurnExpiresAt; },
    set sidebarMixedVideoTurnExpiresAt(value) { sidebarMixedVideoTurnExpiresAt = value; },
    get sidebarMixedCurrentVideoItem() { return sidebarMixedCurrentVideoItem; },
    set sidebarMixedCurrentVideoItem(value) { sidebarMixedCurrentVideoItem = value; },
    get sidebarMixedOnVideoFinished() { return sidebarMixedOnVideoFinished; },
    set sidebarMixedOnVideoFinished(value) { sidebarMixedOnVideoFinished = value; },
    get rightSidebarHybridPhase() { return rightSidebarHybridPhase; },
    set rightSidebarHybridPhase(value) { rightSidebarHybridPhase = value; },
    get rightSidebarHybridVideoCountInPhase() { return rightSidebarHybridVideoCountInPhase; },
    set rightSidebarHybridVideoCountInPhase(value) { rightSidebarHybridVideoCountInPhase = value; },
    get rightSidebarHybridImageCountInPhase() { return rightSidebarHybridImageCountInPhase; },
    set rightSidebarHybridImageCountInPhase(value) { rightSidebarHybridImageCountInPhase = value; },
    get rightSidebarHybridSwitching() { return rightSidebarHybridSwitching; },
    set rightSidebarHybridSwitching(value) { rightSidebarHybridSwitching = value; },
    get rightSidebarHybridLastCompletedAt() { return rightSidebarHybridLastCompletedAt; },
    set rightSidebarHybridLastCompletedAt(value) { rightSidebarHybridLastCompletedAt = value; },
    get forceVideoPlaylistApplyOnce() { return forceVideoPlaylistApplyOnce; },
    set forceVideoPlaylistApplyOnce(value) { forceVideoPlaylistApplyOnce = value; },
    get rightSidebarHybridConfigSignature() { return rightSidebarHybridConfigSignature; },
    set rightSidebarHybridConfigSignature(value) { rightSidebarHybridConfigSignature = value; },
    get rightSidebarHybridHasShownAnyImage() { return rightSidebarHybridHasShownAnyImage; },
    set rightSidebarHybridHasShownAnyImage(value) { rightSidebarHybridHasShownAnyImage = value; },
    get videoPlaylistItems() { return videoPlaylistItems; },
    set videoPlaylistItems(value) { videoPlaylistItems = value; },
    get currentVideoIndex() { return currentVideoIndex; },
    set currentVideoIndex(value) { currentVideoIndex = value; },
    get imageSlideTimer() { return imageSlideTimer; },
    set imageSlideTimer(value) { imageSlideTimer = value; },
};

const rightSidebarMediaModeModule = createRightSidebarMediaModeModule({
    state: rightSidebarMediaModeState,
    visualConfig,
    toBoolean,
    tvImageSlide,
    buildDedicatedFullScreenSlideSignature,
    syncDedicatedFullScreenCompletionState,
    parseConfiguredDedicatedFullScreenSlideUrls,
    shouldSkipDedicatedFullScreenSlideForCurrentLoad,
    markDedicatedFullScreenSlideCycleAsCompleted,
    stopDedicatedFullScreenSlideMode,
    forceRestoreMainLayoutAfterDedicatedSlide,
    hasStaleDedicatedFullScreenSlideSession,
    completeDedicatedFullScreenSlideCycle,
    startDedicatedFullScreenSlideMode,
    isVideoFullscreenModeActive,
    clearSidebarProductTimer,
    hideSidebarProductCard,
    stopVideoPlaybackForImageMode,
    startImageSlideMode,
    stopImageSlideMode,
    startSidebarProductsBeforeImagesMode,
    startSidebarProductsMixedWithImagesMode,
    startSidebarProductsMixedWithMediaMode,
    startSidebarProductsOnlyMode,
    parseConfiguredVideoUrls,
    applyHybridImageLayout,
    loadVideoPlaylist,
    startHybridImagePhase,
});

const rightSidebarMixedModeState = {
    get sidebarMixedModeSignature() { return sidebarMixedModeSignature; },
    set sidebarMixedModeSignature(value) { sidebarMixedModeSignature = value; },
    get sidebarProductTimer() { return sidebarProductTimer; },
    set sidebarProductTimer(value) { sidebarProductTimer = value; },
    get sidebarMixedVideoTurnActive() { return sidebarMixedVideoTurnActive; },
    set sidebarMixedVideoTurnActive(value) { sidebarMixedVideoTurnActive = value; },
    get sidebarMixedVideoItems() { return sidebarMixedVideoItems; },
    set sidebarMixedVideoItems(value) { sidebarMixedVideoItems = value; },
    get sidebarProductItems() { return sidebarProductItems; },
    set sidebarProductItems(value) { sidebarProductItems = value; },
    get sidebarMixedImageUrls() { return sidebarMixedImageUrls; },
    set sidebarMixedImageUrls(value) { sidebarMixedImageUrls = value; },
    get imageSlideUrls() { return imageSlideUrls; },
    set imageSlideUrls(value) { imageSlideUrls = value; },
    get imageSlideSettingsByUrl() { return imageSlideSettingsByUrl; },
    set imageSlideSettingsByUrl(value) { imageSlideSettingsByUrl = value; },
    get sidebarProductIndex() { return sidebarProductIndex; },
    set sidebarProductIndex(value) { sidebarProductIndex = value; },
    get sidebarMixedImageIndex() { return sidebarMixedImageIndex; },
    set sidebarMixedImageIndex(value) { sidebarMixedImageIndex = value; },
    get sidebarMixedVideoIndex() { return sidebarMixedVideoIndex; },
    set sidebarMixedVideoIndex(value) { sidebarMixedVideoIndex = value; },
    get sidebarMixedTurnIndex() { return sidebarMixedTurnIndex; },
    set sidebarMixedTurnIndex(value) { sidebarMixedTurnIndex = value; },
    get sidebarMixedVideoTurnExpiresAt() { return sidebarMixedVideoTurnExpiresAt; },
    set sidebarMixedVideoTurnExpiresAt(value) { sidebarMixedVideoTurnExpiresAt = value; },
    get sidebarMixedCurrentVideoItem() { return sidebarMixedCurrentVideoItem; },
    set sidebarMixedCurrentVideoItem(value) { sidebarMixedCurrentVideoItem = value; },
    get sidebarMixedOnVideoFinished() { return sidebarMixedOnVideoFinished; },
    set sidebarMixedOnVideoFinished(value) { sidebarMixedOnVideoFinished = value; },
};

const rightSidebarMixedModeModule = createRightSidebarMixedModeModule({
    state: rightSidebarMixedModeState,
    visualConfig,
    toBoolean,
    tvImageSlide,
    normalizeSidebarProductItems,
    parseConfiguredImageSlideUrls,
    parseConfiguredVideoUrls,
    clearSidebarProductTimer,
    stopVideoPlaybackForImageMode,
    stopImageSlideMode,
    hideSidebarProductCard,
    hideSlideTextOverlays,
    renderSidebarProductItem,
    showImageSlideAt,
    applyVideoSource,
    getYouTubeVideoId,
    resolveEmbedUrl,
});

const rightSidebarProductCardRendererModule = createRightSidebarProductCardRendererModule({
    visualConfig,
    ensureSidebarProductCard,
    toBoolean,
    normalizeImageSrcCandidate,
    resolveRenderableImageUrl,
    buildImageFallbackCandidates,
    escapeHtmlAttribute,
});

const rightSidebarProductSequencesState = {
    get sidebarProductTimer() { return sidebarProductTimer; },
    set sidebarProductTimer(value) { sidebarProductTimer = value; },
    get sidebarMixedModeSignature() { return sidebarMixedModeSignature; },
    set sidebarMixedModeSignature(value) { sidebarMixedModeSignature = value; },
    get sidebarMixedVideoItems() { return sidebarMixedVideoItems; },
    set sidebarMixedVideoItems(value) { sidebarMixedVideoItems = value; },
    get sidebarMixedVideoIndex() { return sidebarMixedVideoIndex; },
    set sidebarMixedVideoIndex(value) { sidebarMixedVideoIndex = value; },
    get sidebarMixedTurnIndex() { return sidebarMixedTurnIndex; },
    set sidebarMixedTurnIndex(value) { sidebarMixedTurnIndex = value; },
    get sidebarMixedVideoTurnActive() { return sidebarMixedVideoTurnActive; },
    set sidebarMixedVideoTurnActive(value) { sidebarMixedVideoTurnActive = value; },
    get sidebarMixedVideoTurnExpiresAt() { return sidebarMixedVideoTurnExpiresAt; },
    set sidebarMixedVideoTurnExpiresAt(value) { sidebarMixedVideoTurnExpiresAt = value; },
    get sidebarMixedCurrentVideoItem() { return sidebarMixedCurrentVideoItem; },
    set sidebarMixedCurrentVideoItem(value) { sidebarMixedCurrentVideoItem = value; },
    get sidebarMixedOnVideoFinished() { return sidebarMixedOnVideoFinished; },
    set sidebarMixedOnVideoFinished(value) { sidebarMixedOnVideoFinished = value; },
    get sidebarProductItems() { return sidebarProductItems; },
    set sidebarProductItems(value) { sidebarProductItems = value; },
    get sidebarProductIndex() { return sidebarProductIndex; },
    set sidebarProductIndex(value) { sidebarProductIndex = value; },
};

const rightSidebarProductSequencesModule = createRightSidebarProductSequencesModule({
    state: rightSidebarProductSequencesState,
    visualConfig,
    normalizeSidebarProductItems,
    clearSidebarProductTimer,
    stopVideoPlaybackForImageMode,
    stopImageSlideMode,
    hideSidebarProductCard,
    renderSidebarProductItem,
    startImageSlideMode,
});

function applyProductsRefreshInterval(nextSeconds) {
    const normalized = Math.max(5, Math.min(3600, Number(nextSeconds || 30)));

    if (productsRefreshTimer && refreshSeconds === normalized) {
        return;
    }

    refreshSeconds = normalized;
    localStorage.setItem('tv_refresh_seconds', String(refreshSeconds));

    if (productsRefreshTimer) {
        clearInterval(productsRefreshTimer);
        productsRefreshTimer = null;
    }

    productsRefreshTimer = setInterval(() => {
        if (getReliableDeviceToken()) {
            loadProducts();
        }
    }, refreshSeconds * 1000);
}

function isFullscreenSupported() {
    const element = document.documentElement;

    return Boolean(
        document.fullscreenEnabled
        || document.webkitFullscreenEnabled
        || document.msFullscreenEnabled
        || typeof element.requestFullscreen === 'function'
        || typeof element.webkitRequestFullscreen === 'function'
        || typeof element.msRequestFullscreen === 'function'
        || (tvVideo && typeof tvVideo.webkitEnterFullscreen === 'function')
    );
}

function getCurrentFullscreenElement() {
    return document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement || null;
}

function isCompactViewport() {
    try {
        const byWidth = window.matchMedia('(max-width: 1023px)').matches;
        const byTouch = window.matchMedia('(hover: none) and (pointer: coarse)').matches;
        const byAndroidUa = /android/i.test(String(navigator.userAgent || ''));
        return byWidth || byTouch || byAndroidUa;
    } catch (_error) {
        return false;
    }
}

function isAndroidDevice() {
    try {
        return /android/i.test(String(navigator.userAgent || ''));
    } catch (_error) {
        return false;
    }
}

function isCompactSidebarActive() {
    return isCompactViewport() && toBoolean(visualConfig.showRightSidebarPanel, true);
}

function getCompactListScale() {
    if (isCompactSidebarActive()) {
        return 0.72;
    }

    if (isCompactViewport()) {
        return 0.85;
    }

    return 1;
}

function getRightSidebarLayoutDimensions() {
    if (!isCompactViewport()) {
        return {
            compactWidthPx: 0,
            compactMinHeightPx: 0,
            compactMaxHeightPx: 0,
        };
    }

    const compactBaseWidth = Math.round(window.innerWidth * 0.36);
    let compactWidth = Math.max(110, Math.min(420, compactBaseWidth));
    const visualViewportHeight = Number(window.visualViewport?.height || 0);
    const innerViewportHeight = Number(window.innerHeight || 0);
    const viewportHeight = Math.max(320, visualViewportHeight || innerViewportHeight);

    const shellPaddingTop = tvShell ? Number.parseFloat(window.getComputedStyle(tvShell).paddingTop || '0') : 0;
    const shellPaddingBottom = tvShell ? Number.parseFloat(window.getComputedStyle(tvShell).paddingBottom || '0') : 0;
    const mainGap = tvMain ? Number.parseFloat(window.getComputedStyle(tvMain).rowGap || window.getComputedStyle(tvMain).gap || '0') : 0;
    const headerHeight = tvHeader && tvHeader.offsetParent !== null ? tvHeader.getBoundingClientRect().height : 0;
    const footerHeight = tvFooter && tvFooter.offsetParent !== null ? tvFooter.getBoundingClientRect().height : 0;
    const availablePanelHeight = Math.max(
        220,
        Math.round(viewportHeight - shellPaddingTop - shellPaddingBottom - headerHeight - footerHeight - mainGap - 12)
    );

    let compactMaxHeight = Math.max(260, Math.min(980, Math.round(availablePanelHeight * 0.96)));
    let compactMinHeight = Math.max(180, Math.min(compactMaxHeight - 48, Math.round(availablePanelHeight * 0.74)));

    if (isAndroidDevice()) {
        const configuredAndroidWidth = Math.max(0, Number(visualConfig.rightSidebarAndroidWidth || 0));
        const configuredAndroidHeight = Math.max(0, Number(visualConfig.rightSidebarAndroidHeight || 0));

        if (configuredAndroidWidth > 0) {
            compactWidth = Math.max(90, Math.min(700, Math.round(configuredAndroidWidth)));
        }

        if (configuredAndroidHeight > 0) {
            compactMaxHeight = Math.max(140, Math.min(1400, Math.round(configuredAndroidHeight)));
            compactMinHeight = Math.max(120, Math.min(compactMaxHeight, compactMaxHeight - 24));
        }
    }

    return {
        compactWidthPx: compactWidth,
        compactMinHeightPx: compactMinHeight,
        compactMaxHeightPx: compactMaxHeight,
    };
}

function refreshCompactResponsiveLayout() {
    applyCompactRightSidebarLayout();
    applyLeftVerticalLogoVisibility();
}

function scheduleCompactResponsiveLayoutRefresh() {
    if (compactLayoutRefreshRaf !== null) {
        return;
    }

    compactLayoutRefreshRaf = window.requestAnimationFrame(() => {
        compactLayoutRefreshRaf = null;
        refreshCompactResponsiveLayout();
    });
}

function resolveSafeProductsApiEndpoint() {
    const fallbackEndpoint = '/api/tv/produtos';
    const configuredEndpoint = String(apiEndpoint || '').trim();

    if (configuredEndpoint === '') {
        return fallbackEndpoint;
    }

    try {
        const resolved = new URL(configuredEndpoint, window.location.href);
        const current = new URL(window.location.href);
        const isLoopbackTarget = /^(localhost|127\.0\.0\.1)$/i.test(resolved.hostname);
        const isDifferentLoopback = isLoopbackTarget && resolved.hostname !== current.hostname;
        const isCrossOrigin = resolved.origin !== current.origin;

        if (isDifferentLoopback || isCrossOrigin) {
            return fallbackEndpoint;
        }

        return resolved.pathname + resolved.search;
    } catch (_error) {
        return fallbackEndpoint;
    }
}

function requestDocumentFullscreen() {
    const activated = openFullscreenNow();
    if (activated) {
        return Promise.resolve();
    }

    return Promise.reject(new Error('Fullscreen API nao suportada ou bloqueada.'));
}

function openFullscreenNow() {
    const element = document.documentElement;

    try {
        if (typeof element.requestFullscreen === 'function') {
            element.requestFullscreen();
            return true;
        }

        if (typeof element.webkitRequestFullscreen === 'function') {
            element.webkitRequestFullscreen();
            return true;
        }

        if (typeof element.msRequestFullscreen === 'function') {
            element.msRequestFullscreen();
            return true;
        }

        if (tvVideo && typeof tvVideo.webkitEnterFullscreen === 'function') {
            tvVideo.webkitEnterFullscreen();
            return true;
        }
    } catch (_error) {
        return false;
    }

    return false;
}

window.abrirTelaCheia = function abrirTelaCheia() {
    const activated = openFullscreenNow();

    if (activated) {
        updateStatus('Tela cheia ativada para teste.');
        setFullscreenTestButtonState();
        return true;
    }

    updateStatus('Nao foi possivel ativar tela cheia. Tente em modo kiosk.', true);
    return false;
};

function exitDocumentFullscreen() {
    if (typeof document.exitFullscreen === 'function') {
        return document.exitFullscreen();
    }

    if (typeof document.webkitExitFullscreen === 'function') {
        return Promise.resolve(document.webkitExitFullscreen());
    }

    if (typeof document.msExitFullscreen === 'function') {
        return Promise.resolve(document.msExitFullscreen());
    }

    return Promise.resolve();
}

function clearAutoFullscreenRetryTimer() {
    if (!autoFullscreenRetryTimer) {
        return;
    }

    clearTimeout(autoFullscreenRetryTimer);
    autoFullscreenRetryTimer = null;
}

function shouldShowFullscreenBlockedWarning() {
    return true;
}

function scheduleAutoFullscreenRetry(delayMs = 2500) {
    if (!isFullscreenSupported() || getCurrentFullscreenElement()) {
        return;
    }

    clearAutoFullscreenRetryTimer();
    autoFullscreenRetryTimer = setTimeout(() => {
        autoFullscreenRetryTimer = null;
        applyAutoFullscreenPreference();
    }, Math.max(500, Number(delayMs) || 2500));
}

function toBoolean(value, fallback = false) {
    if (value === null || value === undefined) {
        return fallback;
    }

    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value !== 0;
    }

    const normalized = String(value).trim().toLowerCase();

    if (['1', 'true', 'on', 'yes', 'sim'].includes(normalized)) {
        return true;
    }

    if (['0', 'false', 'off', 'no', 'não', 'nao', ''].includes(normalized)) {
        return false;
    }

    return fallback;
}

function escapeHtmlAttribute(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function resolveRenderableImageUrl(rawUrl) {
    const value = String(rawUrl || '').trim();
    if (!value) {
        return '';
    }

    if (/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/storage\//i.test(value)) {
        return value.replace(/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/storage\//i, '/storage/');
    }

    if (/^storage\//i.test(value)) {
        return `/${value.replace(/^\/+/, '')}`;
    }

    if (/^\/storage\//i.test(value)) {
        return value;
    }

    if (/^\/\//.test(value)) {
        return `${window.location.protocol}${value}`;
    }

    return value;
}

function resolveAlternateRenderableImageUrl(rawUrl, currentResolvedUrl = '') {
    const original = String(rawUrl || '').trim();
    if (!original) {
        return '';
    }

    const current = String(currentResolvedUrl || '').trim();
    const isHttpsPage = String(window.location.protocol || '').toLowerCase() === 'https:';

    if (isHttpsPage && /^http:\/\//i.test(original)) {
        const httpsCandidate = original.replace(/^http:\/\//i, 'https://');
        if (httpsCandidate !== current) {
            return httpsCandidate;
        }
    }

    if (/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/storage\//i.test(original)) {
        const storageCandidate = original.replace(/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/storage\//i, '/storage/');
        if (storageCandidate !== current) {
            return storageCandidate;
        }
    }

    return '';
}

function normalizeImageSrcCandidate(input) {
    let value = String(input || '').trim();
    if (!value) {
        return '';
    }

    if (/^storage\//i.test(value)) {
        value = `/${value.replace(/^\/+/, '')}`;
    }

    if (/^\/\//.test(value)) {
        value = `${window.location.protocol}${value}`;
    }

    try {
        return encodeURI(value);
    } catch (_error) {
        return value;
    }
}

function markAudioAutoplayUnlocked() {
    if (audioAutoplayUnlocked) {
        return;
    }

    audioAutoplayUnlocked = true;
    localStorage.setItem(AUDIO_UNLOCK_STORAGE_KEY, '1');
}

function clearVideoFallbackTimer() {
    if (videoFallbackTimer) {
        clearTimeout(videoFallbackTimer);
        videoFallbackTimer = null;
    }
}

function clearInitialVideoAutoplayRetryTimer() {
    if (initialVideoAutoplayRetryTimer) {
        clearTimeout(initialVideoAutoplayRetryTimer);
        initialVideoAutoplayRetryTimer = null;
    }
}

function clearImageSlideTimer() {
    if (imageSlideTimer) {
        clearInterval(imageSlideTimer);
        imageSlideTimer = null;
    }
}

function clearFullScreenSlideTimer() {
    if (fullScreenSlideTimer) {
        clearInterval(fullScreenSlideTimer);
        fullScreenSlideTimer = null;
    }
}

function clearFullScreenSlideExitTimer() {
    if (fullScreenSlideExitTimer) {
        clearTimeout(fullScreenSlideExitTimer);
        fullScreenSlideExitTimer = null;
    }
}

function clearFullScreenSlideReturnTimer() {
    if (fullScreenSlideReturnTimer) {
        clearTimeout(fullScreenSlideReturnTimer);
        fullScreenSlideReturnTimer = null;
    }
}

function parseConfiguredUrlLines(rawValue) {
    const raw = String(rawValue || '').trim();
    if (!raw) {
        return [];
    }

    return raw
        .split(/\r?\n/)
        .map((item) => extractVideoUrl(item.trim().replace(/^['\"]+|['\"]+$/g, '')))
        .filter(Boolean);
}

function parseConfiguredDedicatedFullScreenSlideUrls() {
    return dedicatedFullScreenSlideModule.parseConfiguredDedicatedFullScreenSlideUrls();
}

function buildDedicatedFullScreenSlideSignature() {
    return dedicatedFullScreenSlideModule.buildDedicatedFullScreenSlideSignature();
}

function isDedicatedFullScreenSlideCompletedForSignature(signature) {
    return dedicatedFullScreenSlideModule.isDedicatedFullScreenSlideCompletedForSignature(signature);
}

function markDedicatedFullScreenSlideCycleAsCompleted(signature = '') {
    dedicatedFullScreenSlideModule.markDedicatedFullScreenSlideCycleAsCompleted(signature);
}

function shouldSkipDedicatedFullScreenSlideForCurrentLoad(hasDedicatedFullScreenSlide, options = {}) {
    return dedicatedFullScreenSlideModule.shouldSkipDedicatedFullScreenSlideForCurrentLoad(hasDedicatedFullScreenSlide, options);
}

function isVideoFullscreenModeActive() {
    const hasFullscreenClass = document.body.classList.contains('tv-video-fullscreen');
    if (!hasFullscreenClass) {
        return false;
    }

    const isElementVisible = (element) => {
        if (!element) {
            return false;
        }

        if (element.classList.contains('hidden')) {
            return false;
        }

        const style = window.getComputedStyle(element);
        return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity || 1) > 0;
    };

    const hasVisibleVideo = isElementVisible(tvVideo) || isElementVisible(tvEmbed);
    if (hasVisibleVideo) {
        return true;
    }

    // Stale fullscreen-video class can block dedicated fullscreen indefinitely.
    document.body.classList.remove('tv-video-fullscreen');
    return false;
}

function getDedicatedFullScreenSlideReturnDelaySeconds() {
    return dedicatedFullScreenSlideModule.getDedicatedFullScreenSlideReturnDelaySeconds();
}

function scheduleDedicatedFullScreenSlideRestart() {
    dedicatedFullScreenSlideModule.scheduleDedicatedFullScreenSlideRestart();
}

function completeDedicatedFullScreenSlideCycle() {
    dedicatedFullScreenSlideModule.completeDedicatedFullScreenSlideCycle();
}

function forceRestoreMainLayoutAfterDedicatedSlide() {
    document.body.classList.remove('tv-image-fullscreen');
    document.body.classList.remove('tv-video-fullscreen');
    clearImageFullscreenShellOverrides();

    if (tvImageSlide) {
        tvImageSlide.classList.add('hidden');
        tvImageSlide.removeAttribute('src');
        tvImageSlide.style.removeProperty('position');
        tvImageSlide.style.removeProperty('inset');
        tvImageSlide.style.removeProperty('z-index');
        tvImageSlide.style.removeProperty('width');
        tvImageSlide.style.removeProperty('height');
        tvImageSlide.style.removeProperty('max-width');
        tvImageSlide.style.removeProperty('max-height');
        tvImageSlide.style.removeProperty('border-radius');
        tvImageSlide.style.removeProperty('background');
    }

    hideSlideTextOverlays();

    const fullscreenElement = getCurrentFullscreenElement();
    if (fullscreenElement) {
        exitDocumentFullscreen().catch(() => {});
    }

    // Restore configured sidebar visuals after fullscreen overrides are cleared.
    applyVideoBackground();
    applyRightSidebarBorder();
}

function hasStaleDedicatedFullScreenSlideSession() {
    return dedicatedFullScreenSlideModule.hasStaleDedicatedFullScreenSlideSession();
}

function startDedicatedFullScreenSlideMode() {
    return dedicatedFullScreenSlideModule.startDedicatedFullScreenSlideMode();
}

function stopDedicatedFullScreenSlideMode() {
    dedicatedFullScreenSlideModule.stopDedicatedFullScreenSlideMode();
}

function getStoredCompletedFullscreenSlideSignature() {
    try {
        return String(localStorage.getItem(FULLSCREEN_SLIDE_COMPLETED_SIGNATURE_KEY) || '').trim();
    } catch (_error) {
        return '';
    }
}

function storeCompletedFullscreenSlideSignature(signature) {
    try {
        const normalized = String(signature || '').trim();
        if (!normalized) {
            localStorage.removeItem(FULLSCREEN_SLIDE_COMPLETED_SIGNATURE_KEY);
            writeCompletedSignatureToWindowName('');
            return;
        }

        localStorage.setItem(FULLSCREEN_SLIDE_COMPLETED_SIGNATURE_KEY, normalized);
        writeCompletedSignatureToWindowName(normalized);
    } catch (_error) {
        writeCompletedSignatureToWindowName(signature);
    }
}

function syncDedicatedFullScreenCompletionState() {
    dedicatedFullScreenSlideModule.syncDedicatedFullScreenCompletionState();
}

function parseConfiguredImageSlideUrls() {
    const inputUrls = parseConfiguredUrlLines(visualConfig.rightSidebarImageUrls);
    if (inputUrls.length === 0) {
        return [];
    }

    const normalizeSlideUrl = (value) => {
        const url = String(value || '').trim();
        if (!url) {
            return '';
        }

        let transformed = url;

        if (/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/storage\//i.test(transformed)) {
            transformed = transformed.replace(/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?\/storage\//i, '/storage/');
        }

        if (/^storage\//i.test(transformed)) {
            transformed = `/${transformed.replace(/^\/+/, '')}`;
        }

        return transformed;
    };

    const stripQueryAndHash = (input) => String(input || '').split('#')[0].split('?')[0].trim();

    const schedules = Array.isArray(visualConfig.rightSidebarImageSchedules)
        ? visualConfig.rightSidebarImageSchedules
        : [];
    const isAndroidRuntime = isAndroidDevice();
    const parsePlatformEnabled = (value, defaultValue = true) => {
        if (value === undefined || value === null || value === '') {
            return defaultValue;
        }

        if (typeof value === 'boolean') {
            return value;
        }

        if (typeof value === 'number') {
            return value === 1;
        }

        const normalized = String(value).trim().toLowerCase();
        return normalized === '1' || normalized === 'true' || normalized === 'on' || normalized === 'yes';
    };

    const scheduleByUrl = new Map();
    const scheduleByLooseUrl = new Map();
    const scheduleByFileName = new Map();
    const toLooseUrlKey = (value) => stripQueryAndHash(normalizeSlideUrl(value)).replace(/\/$/, '');
    const toFileNameKey = (value) => {
        const normalized = stripQueryAndHash(normalizeSlideUrl(value));
        if (!normalized) {
            return '';
        }

        const clean = normalized.split('/').pop() || '';
        try {
            return decodeURIComponent(clean).trim().toLowerCase();
        } catch (_error) {
            return clean.trim().toLowerCase();
        }
    };

    const resolveScheduleForUrl = (value) => {
        const exact = scheduleByUrl.get(normalizeSlideUrl(value));
        if (exact) {
            return exact;
        }

        const loose = scheduleByLooseUrl.get(toLooseUrlKey(value));
        if (loose) {
            return loose;
        }

        const fileNameKey = toFileNameKey(value);
        if (!fileNameKey) {
            return null;
        }

        return scheduleByFileName.get(fileNameKey) || null;
    };

    schedules.forEach((entry) => {
        const normalizedUrl = normalizeSlideUrl(entry?.url);
        if (!normalizedUrl) {
            return;
        }

        const legacyImageHeight = Math.max(0, Number(entry?.imageHeight || 0) || 0);
        const legacyImageWidth = Math.max(0, Number(entry?.imageWidth || 0) || 0);
        const legacyVerticalOffset = Math.max(-300, Math.min(300, Number(entry?.verticalOffset || 0) || 0));

        const normalizedSchedule = {
            name: String(entry?.name || '').trim(),
            startDate: String(entry?.startDate || '').trim(),
            endDate: String(entry?.endDate || '').trim(),
            enabledForWindows: parsePlatformEnabled(entry?.enabledForWindows, true),
            enabledForAndroid: parsePlatformEnabled(entry?.enabledForAndroid, parsePlatformEnabled(entry?.enabledForWindows, true)),
            windowsImageHeight: Math.max(0, Number(entry?.windowsImageHeight ?? legacyImageHeight) || 0),
            windowsImageWidth: Math.max(0, Number(entry?.windowsImageWidth ?? legacyImageWidth) || 0),
            windowsVerticalOffset: Math.max(-300, Math.min(300, Number(entry?.windowsVerticalOffset ?? legacyVerticalOffset) || 0)),
            windowsImageFit: String(entry?.windowsImageFit || entry?.imageFit || 'scale-down').toLowerCase(),
            windowsImageBackgroundColor: String(entry?.windowsImageBackgroundColor || '#000000').trim(),
            windowsShowName: parsePlatformEnabled(entry?.windowsShowName, false),
            windowsShowPrice: parsePlatformEnabled(entry?.windowsShowPrice, false),
            windowsPriceText: String(entry?.windowsPriceText || '').trim(),
            windowsNameFontSize: Math.max(8, Math.min(120, Number(entry?.windowsNameFontSize || 18) || 18)),
            windowsPriceFontSize: Math.max(8, Math.min(120, Number(entry?.windowsPriceFontSize || 22) || 22)),
            windowsTextFontFamily: String(entry?.windowsTextFontFamily || 'arial').toLowerCase(),
            windowsNamePosition: String(entry?.windowsNamePosition || 'top').toLowerCase(),
            windowsPricePosition: String(entry?.windowsPricePosition || 'bottom').toLowerCase(),
            windowsNameColor: String(entry?.windowsNameColor || '#FFFFFF').trim(),
            windowsNameBadgeEnabled: parsePlatformEnabled(entry?.windowsNameBadgeEnabled, true),
            windowsNameBadgeColor: String(entry?.windowsNameBadgeColor || '#0F172A').trim(),
            windowsPriceColor: String(entry?.windowsPriceColor || '#FDE68A').trim(),
            windowsPriceBadgeEnabled: parsePlatformEnabled(entry?.windowsPriceBadgeEnabled, true),
            windowsPriceBadgeColor: String(entry?.windowsPriceBadgeColor || '#0F172A').trim(),
            androidImageHeight: Math.max(0, Number(entry?.androidImageHeight ?? legacyImageHeight) || 0),
            androidImageWidth: Math.max(0, Number(entry?.androidImageWidth ?? legacyImageWidth) || 0),
            androidVerticalOffset: Math.max(-300, Math.min(300, Number(entry?.androidVerticalOffset ?? legacyVerticalOffset) || 0)),
            androidImageFit: String(entry?.androidImageFit || entry?.windowsImageFit || entry?.imageFit || 'scale-down').toLowerCase(),
            androidImageBackgroundColor: String(entry?.androidImageBackgroundColor || entry?.windowsImageBackgroundColor || '#000000').trim(),
            androidShowName: parsePlatformEnabled(entry?.androidShowName, parsePlatformEnabled(entry?.windowsShowName, false)),
            androidShowPrice: parsePlatformEnabled(entry?.androidShowPrice, parsePlatformEnabled(entry?.windowsShowPrice, false)),
            androidPriceText: String(entry?.androidPriceText || entry?.windowsPriceText || '').trim(),
            androidNameFontSize: Math.max(8, Math.min(120, Number(entry?.androidNameFontSize ?? entry?.windowsNameFontSize ?? 18) || 18)),
            androidPriceFontSize: Math.max(8, Math.min(120, Number(entry?.androidPriceFontSize ?? entry?.windowsPriceFontSize ?? 22) || 22)),
            androidTextFontFamily: String(entry?.androidTextFontFamily || entry?.windowsTextFontFamily || 'arial').toLowerCase(),
            androidNamePosition: String(entry?.androidNamePosition || entry?.windowsNamePosition || 'top').toLowerCase(),
            androidPricePosition: String(entry?.androidPricePosition || entry?.windowsPricePosition || 'bottom').toLowerCase(),
            androidNameColor: String(entry?.androidNameColor || entry?.windowsNameColor || '#FFFFFF').trim(),
            androidNameBadgeEnabled: parsePlatformEnabled(entry?.androidNameBadgeEnabled, parsePlatformEnabled(entry?.windowsNameBadgeEnabled, true)),
            androidNameBadgeColor: String(entry?.androidNameBadgeColor || entry?.windowsNameBadgeColor || '#0F172A').trim(),
            androidPriceColor: String(entry?.androidPriceColor || entry?.windowsPriceColor || '#FDE68A').trim(),
            androidPriceBadgeEnabled: parsePlatformEnabled(entry?.androidPriceBadgeEnabled, parsePlatformEnabled(entry?.windowsPriceBadgeEnabled, true)),
            androidPriceBadgeColor: String(entry?.androidPriceBadgeColor || entry?.windowsPriceBadgeColor || '#0F172A').trim(),
        };

        scheduleByUrl.set(normalizedUrl, normalizedSchedule);
        scheduleByLooseUrl.set(toLooseUrlKey(normalizedUrl), normalizedSchedule);
        const fileNameKey = toFileNameKey(normalizedUrl);
        if (fileNameKey && !scheduleByFileName.has(fileNameKey)) {
            scheduleByFileName.set(fileNameKey, normalizedSchedule);
        }
    });

    const localToday = new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, 10);

    const shouldShowByDate = (url) => {
        const schedule = resolveScheduleForUrl(url);
        if (!schedule) {
            return true;
        }

        const startDate = schedule.startDate;
        const endDate = schedule.endDate;

        if (startDate && localToday < startDate) {
            return false;
        }

        if (endDate && localToday > endDate) {
            return false;
        }

        if (isAndroidRuntime && schedule.enabledForAndroid === false) {
            return false;
        }

        if (!isAndroidRuntime && schedule.enabledForWindows === false) {
            return false;
        }

        return true;
    };

    const filteredUrls = inputUrls
        .filter((url) => Boolean(url) && shouldShowByDate(url));

    const nextSettingsMap = new Map();
    filteredUrls.forEach((url) => {
        const normalized = normalizeSlideUrl(url);
        const schedule = resolveScheduleForUrl(normalized);
        if (!schedule) {
            return;
        }

        const selectedHeight = isAndroidRuntime ? schedule.androidImageHeight : schedule.windowsImageHeight;
        const selectedWidth = isAndroidRuntime ? schedule.androidImageWidth : schedule.windowsImageWidth;
        const selectedOffset = isAndroidRuntime ? schedule.androidVerticalOffset : schedule.windowsVerticalOffset;
        const selectedFitRaw = isAndroidRuntime ? schedule.androidImageFit : schedule.windowsImageFit;
        const selectedBackgroundColor = String(isAndroidRuntime
            ? (schedule.androidImageBackgroundColor || '#000000')
            : (schedule.windowsImageBackgroundColor || '#000000')
        ).trim();
        const selectedFit = selectedFitRaw === 'cover'
            ? 'cover'
            : (selectedFitRaw === 'contain' ? 'contain' : 'scale-down');

        const settings = {
            imageHeight: Math.max(0, Number(selectedHeight || 0) || 0),
            imageWidth: Math.max(0, Number(selectedWidth || 0) || 0),
            verticalOffset: Math.max(-300, Math.min(300, Number(selectedOffset || 0) || 0)),
            imageFit: selectedFit,
            imageBackgroundColor: selectedBackgroundColor,
            showName: isAndroidRuntime ? Boolean(schedule.androidShowName) : Boolean(schedule.windowsShowName),
            showPrice: isAndroidRuntime ? Boolean(schedule.androidShowPrice) : Boolean(schedule.windowsShowPrice),
            priceText: String(isAndroidRuntime ? (schedule.androidPriceText || '') : (schedule.windowsPriceText || '')).trim(),
            nameFontSize: Math.max(8, Math.min(120, Number(isAndroidRuntime ? schedule.androidNameFontSize : schedule.windowsNameFontSize) || 18)),
            priceFontSize: Math.max(8, Math.min(120, Number(isAndroidRuntime ? schedule.androidPriceFontSize : schedule.windowsPriceFontSize) || 22)),
            textFontFamily: String(isAndroidRuntime ? (schedule.androidTextFontFamily || 'arial') : (schedule.windowsTextFontFamily || 'arial')).toLowerCase(),
            namePosition: String(isAndroidRuntime ? (schedule.androidNamePosition || 'top') : (schedule.windowsNamePosition || 'top')).toLowerCase(),
            pricePosition: String(isAndroidRuntime ? (schedule.androidPricePosition || 'bottom') : (schedule.windowsPricePosition || 'bottom')).toLowerCase(),
            nameColor: String(isAndroidRuntime ? (schedule.androidNameColor || '#FFFFFF') : (schedule.windowsNameColor || '#FFFFFF')).trim(),
            nameBadgeEnabled: isAndroidRuntime ? Boolean(schedule.androidNameBadgeEnabled) : Boolean(schedule.windowsNameBadgeEnabled),
            nameBadgeColor: String(isAndroidRuntime ? (schedule.androidNameBadgeColor || '#0F172A') : (schedule.windowsNameBadgeColor || '#0F172A')).trim(),
            priceColor: String(isAndroidRuntime ? (schedule.androidPriceColor || '#FDE68A') : (schedule.windowsPriceColor || '#FDE68A')).trim(),
            priceBadgeEnabled: isAndroidRuntime ? Boolean(schedule.androidPriceBadgeEnabled) : Boolean(schedule.windowsPriceBadgeEnabled),
            priceBadgeColor: String(isAndroidRuntime ? (schedule.androidPriceBadgeColor || '#0F172A') : (schedule.windowsPriceBadgeColor || '#0F172A')).trim(),
            nameText: String(schedule.name || '').trim(),
        };

        nextSettingsMap.set(url, settings);
        if (normalized && normalized !== url) {
            nextSettingsMap.set(normalized, settings);
        }
    });
    imageSlideSettingsByUrl = nextSettingsMap;

    return filteredUrls;
}

function stopVideoPlaybackForImageMode() {
    clearVideoFallbackTimer();
    clearInitialVideoAutoplayRetryTimer();
    clearYouTubeStatePoll();
    clearYouTubePlayKeepAlive();
    clearYouTubeUnmuteDelay();
    clearYouTubeStartupTimeout();

    document.body.classList.remove('tv-video-fullscreen');
    document.body.classList.remove('tv-image-fullscreen');

    if (tvVideo) {
        tvVideo.pause();
        tvVideo.classList.add('hidden');
    }

    if (tvEmbed) {
        tvEmbed.classList.add('hidden');
        tvEmbed.removeAttribute('src');
    }
}

function applyImageFullscreenShellOverrides() {
    // Keep fullscreen behavior scoped to CSS classes to avoid leaking inline
    // sidebar styles after exiting dedicated fullscreen image mode.
}

function clearImageFullscreenShellOverrides() {
    // Intentionally no-op: inline fullscreen shell overrides were removed.
}

function setImageSlideFullscreenMode(enabled = false) {
    const isEnabled = Boolean(enabled);
    document.body.classList.toggle('tv-image-fullscreen', isEnabled);

    if (!isEnabled) {
        clearImageFullscreenShellOverrides();
    }

    if (tvImageSlide) {
        if (isEnabled) {
            applyImageFullscreenShellOverrides();
            tvImageSlide.style.setProperty('position', 'fixed', 'important');
            tvImageSlide.style.setProperty('inset', '0', 'important');
            tvImageSlide.style.setProperty('z-index', '60', 'important');
            tvImageSlide.style.setProperty('width', '100vw', 'important');
            tvImageSlide.style.setProperty('height', '100vh', 'important');
            tvImageSlide.style.setProperty('max-width', '100vw', 'important');
            tvImageSlide.style.setProperty('max-height', '100vh', 'important');
            tvImageSlide.style.setProperty('border-radius', '0', 'important');
            tvImageSlide.style.setProperty('background', '#000', 'important');
        } else {
            tvImageSlide.style.removeProperty('position');
            tvImageSlide.style.removeProperty('inset');
            tvImageSlide.style.removeProperty('z-index');
            tvImageSlide.style.removeProperty('width');
            tvImageSlide.style.removeProperty('height');
            tvImageSlide.style.removeProperty('max-width');
            tvImageSlide.style.removeProperty('max-height');
            tvImageSlide.style.removeProperty('border-radius');
            tvImageSlide.style.removeProperty('background');
        }
    }

    if (isEnabled) {
        document.body.classList.remove('tv-video-fullscreen');
    }
}

function buildImageFallbackCandidates(rawUrl, currentResolvedUrl = '') {
    const candidates = [];
    const seen = new Set();

    const pushCandidate = (value) => {
        const candidate = normalizeImageSrcCandidate(value);
        if (!candidate || seen.has(candidate) || candidate === String(currentResolvedUrl || '').trim()) {
            return;
        }

        seen.add(candidate);
        candidates.push(candidate);
    };

    const raw = normalizeImageSrcCandidate(rawUrl);
    if (raw && raw !== String(currentResolvedUrl || '').trim()) {
        pushCandidate(raw);
    }

    pushCandidate(resolveAlternateRenderableImageUrl(rawUrl, currentResolvedUrl));

    const current = normalizeImageSrcCandidate(currentResolvedUrl);
    if (/^\/storage\//i.test(current)) {
        pushCandidate(`${window.location.origin}${current}`);
    }

    if (/^storage\//i.test(raw)) {
        pushCandidate(`/${raw.replace(/^\/+/, '')}`);
    }

    if (/^\/storage\//i.test(raw)) {
        pushCandidate(`${window.location.origin}${raw}`);
    }

    return candidates;
}

function showImageSlideAt(index) {
    if (!tvImageSlide || imageSlideUrls.length === 0) {
        return;
    }

    currentImageSlideIndex = (index + imageSlideUrls.length) % imageSlideUrls.length;
    const currentSlideUrl = imageSlideUrls[currentImageSlideIndex];
    const resolvedSlideUrl = normalizeImageSrcCandidate(resolveRenderableImageUrl(currentSlideUrl));
    const fallbackCandidates = buildImageFallbackCandidates(currentSlideUrl, resolvedSlideUrl);
    tvImageSlide.dataset.fallbackCandidates = JSON.stringify(fallbackCandidates);
    tvImageSlide.dataset.fallbackIndex = '0';
    tvImageSlide.src = resolvedSlideUrl || normalizeImageSrcCandidate(currentSlideUrl);
    applyConfiguredSlideImageLayout(currentSlideUrl);
    applyConfiguredSlideTextOverlay(currentSlideUrl);
}

function handleCurrentSlideImageLoadError() {
    if (!tvImageSlide || imageSlideUrls.length === 0) {
        return;
    }

    let fallbackCandidates = [];
    try {
        fallbackCandidates = JSON.parse(String(tvImageSlide.dataset.fallbackCandidates || '[]'));
    } catch (_error) {
        fallbackCandidates = [];
    }

    const fallbackIndex = Math.max(0, Number(tvImageSlide.dataset.fallbackIndex || 0) || 0);
    const nextFallback = Array.isArray(fallbackCandidates) ? String(fallbackCandidates[fallbackIndex] || '').trim() : '';
    if (nextFallback) {
        tvImageSlide.dataset.fallbackIndex = String(fallbackIndex + 1);
        tvImageSlide.src = nextFallback;
        return;
    }

    if (forceFullScreenSlideModeActive) {
        if (imageSlideUrls.length <= 1 || currentImageSlideIndex >= imageSlideUrls.length - 1) {
            completeDedicatedFullScreenSlideCycle();
            return;
        }

        fullScreenSlideIndex = Math.max(fullScreenSlideIndex, currentImageSlideIndex + 1);
        showImageSlideAt(currentImageSlideIndex + 1);
        return;
    }

    if (imageSlideUrls.length === 1) {
        setImageSlideFullscreenMode(false);
        tvImageSlide.classList.add('hidden');
        tvImageSlide.removeAttribute('src');
        hideSlideTextOverlays();
        return;
    }

    // Skip broken image and continue the regular looping slide sequence.
    showImageSlideAt(currentImageSlideIndex + 1);
}

function ensureSlideTextOverlays() {
    if (!tvRightSidebarMediaWrap) {
        return;
    }

    if (!(slideNameOverlay instanceof HTMLDivElement)) {
        slideNameOverlay = document.createElement('div');
        slideNameOverlay.id = 'tvImageSlideNameOverlay';
        slideNameOverlay.className = 'hidden';
        slideNameOverlay.style.position = 'absolute';
        slideNameOverlay.style.left = '8px';
        slideNameOverlay.style.right = '8px';
        slideNameOverlay.style.zIndex = '20';
        slideNameOverlay.style.pointerEvents = 'none';
        slideNameOverlay.style.fontWeight = '700';
        slideNameOverlay.style.textAlign = 'center';
        slideNameOverlay.style.color = '#ffffff';
        slideNameOverlay.style.textShadow = '0 2px 6px rgba(0, 0, 0, 0.85)';
        slideNameOverlay.style.letterSpacing = '0.01em';
        slideNameOverlay.style.lineHeight = '1.15';
        slideNameOverlay.style.whiteSpace = 'normal';
        slideNameOverlay.style.wordBreak = 'break-word';
        slideNameOverlay.style.background = 'rgba(15, 23, 42, 0.45)';
        slideNameOverlay.style.borderRadius = '8px';
        slideNameOverlay.style.padding = '4px 8px';
        tvRightSidebarMediaWrap.appendChild(slideNameOverlay);
    }

    if (!(slidePriceOverlay instanceof HTMLDivElement)) {
        slidePriceOverlay = document.createElement('div');
        slidePriceOverlay.id = 'tvImageSlidePriceOverlay';
        slidePriceOverlay.className = 'hidden';
        slidePriceOverlay.style.position = 'absolute';
        slidePriceOverlay.style.left = '8px';
        slidePriceOverlay.style.right = '8px';
        slidePriceOverlay.style.zIndex = '20';
        slidePriceOverlay.style.pointerEvents = 'none';
        slidePriceOverlay.style.fontWeight = '800';
        slidePriceOverlay.style.textAlign = 'center';
        slidePriceOverlay.style.color = '#fef08a';
        slidePriceOverlay.style.textShadow = '0 2px 6px rgba(0, 0, 0, 0.9)';
        slidePriceOverlay.style.letterSpacing = '0.01em';
        slidePriceOverlay.style.lineHeight = '1.1';
        slidePriceOverlay.style.whiteSpace = 'normal';
        slidePriceOverlay.style.wordBreak = 'break-word';
        slidePriceOverlay.style.background = 'rgba(15, 23, 42, 0.5)';
        slidePriceOverlay.style.borderRadius = '8px';
        slidePriceOverlay.style.padding = '4px 8px';
        tvRightSidebarMediaWrap.appendChild(slidePriceOverlay);
    }

    tvRightSidebarMediaWrap.style.position = 'relative';
}

function hideSlideTextOverlays() {
    if (slideNameOverlay) {
        slideNameOverlay.classList.add('hidden');
        slideNameOverlay.textContent = '';
    }

    if (slidePriceOverlay) {
        slidePriceOverlay.classList.add('hidden');
        slidePriceOverlay.textContent = '';
    }
}

function getImageSlideSettings(currentSlideUrl = '') {
    const raw = String(currentSlideUrl || '').trim();
    if (!raw) {
        return null;
    }

    const normalizeForCompare = (value) => String(value || '').split('#')[0].split('?')[0].trim().replace(/\/$/, '');
    const toFileNameKey = (value) => {
        const normalized = normalizeForCompare(value);
        if (!normalized) {
            return '';
        }

        const fileName = normalized.split('/').pop() || '';
        try {
            return decodeURIComponent(fileName).trim().toLowerCase();
        } catch (_error) {
            return fileName.trim().toLowerCase();
        }
    };

    const direct = imageSlideSettingsByUrl.get(raw);
    if (direct) {
        return direct;
    }

    const normalized = resolveRenderableImageUrl(raw);
    if (!normalized) {
        return null;
    }

    const byNormalized = imageSlideSettingsByUrl.get(normalized);
    if (byNormalized) {
        return byNormalized;
    }

    const targetLoose = normalizeForCompare(normalized);
    const targetFile = toFileNameKey(normalized);
    for (const [key, settings] of imageSlideSettingsByUrl.entries()) {
        const keyLoose = normalizeForCompare(key);
        if (keyLoose && keyLoose === targetLoose) {
            return settings;
        }

        if (targetFile && toFileNameKey(key) === targetFile) {
            return settings;
        }
    }

    return null;
}

function applyOverlayBlockPosition(element, position, defaultPosition) {
    if (!element) {
        return;
    }

    const resolved = position === 'bottom' || position === 'top' ? position : defaultPosition;
    if (resolved === 'bottom') {
        element.style.top = '';
        element.style.bottom = '8px';
    } else {
        element.style.top = '8px';
        element.style.bottom = '';
    }
}

function applyConfiguredSlideTextOverlay(currentSlideUrl = '') {
    ensureSlideTextOverlays();

    if (!currentSlideUrl) {
        hideSlideTextOverlays();
        return;
    }

    const perImageSettings = getImageSlideSettings(currentSlideUrl);
    if (!perImageSettings) {
        hideSlideTextOverlays();
        return;
    }

    const fallbackNameFromUrl = (value) => {
        const raw = String(value || '').trim();
        if (!raw) {
            return '';
        }

        const withoutQuery = raw.split('#')[0].split('?')[0];
        const fileName = withoutQuery.split('/').pop() || '';
        if (!fileName) {
            return '';
        }

        const withoutExtension = fileName.replace(/\.[a-z0-9]+$/i, '');
        try {
            return decodeURIComponent(withoutExtension).replace(/[-_]+/g, ' ').trim();
        } catch (_error) {
            return withoutExtension.replace(/[-_]+/g, ' ').trim();
        }
    };

    const fontFamily = resolveTitleFontFamily(String(perImageSettings.textFontFamily || 'arial'));
    const showName = Boolean(perImageSettings.showName);
    const showPrice = Boolean(perImageSettings.showPrice);
    const nameText = String(perImageSettings.nameText || '').trim() || fallbackNameFromUrl(currentSlideUrl);
    const priceText = String(perImageSettings.priceText || '').trim();

    if (slideNameOverlay) {
        if (showName && nameText !== '') {
            slideNameOverlay.textContent = nameText;
            slideNameOverlay.style.fontFamily = fontFamily;
            slideNameOverlay.style.fontSize = `${Math.max(8, Math.min(120, Number(perImageSettings.nameFontSize || 18) || 18))}px`;
            slideNameOverlay.style.color = String(perImageSettings.nameColor || '#FFFFFF');
            slideNameOverlay.style.background = perImageSettings.nameBadgeEnabled === false
                ? 'transparent'
                : String(perImageSettings.nameBadgeColor || '#0F172A');
            applyOverlayBlockPosition(slideNameOverlay, String(perImageSettings.namePosition || 'top').toLowerCase(), 'top');
            slideNameOverlay.classList.remove('hidden');
        } else {
            slideNameOverlay.classList.add('hidden');
            slideNameOverlay.textContent = '';
        }
    }

    if (slidePriceOverlay) {
        if (showPrice && priceText !== '') {
            slidePriceOverlay.textContent = priceText;
            slidePriceOverlay.style.fontFamily = fontFamily;
            slidePriceOverlay.style.fontSize = `${Math.max(8, Math.min(120, Number(perImageSettings.priceFontSize || 22) || 22))}px`;
            slidePriceOverlay.style.color = String(perImageSettings.priceColor || '#FDE68A');
            slidePriceOverlay.style.background = perImageSettings.priceBadgeEnabled === false
                ? 'transparent'
                : String(perImageSettings.priceBadgeColor || '#0F172A');
            applyOverlayBlockPosition(slidePriceOverlay, String(perImageSettings.pricePosition || 'bottom').toLowerCase(), 'bottom');
            slidePriceOverlay.classList.remove('hidden');
        } else {
            slidePriceOverlay.classList.add('hidden');
            slidePriceOverlay.textContent = '';
        }
    }
}

function applyConfiguredSlideImageLayout(currentSlideUrl = '') {
    if (!tvImageSlide) {
        return;
    }

    const isCurrentUrlFromDedicatedFullScreenSet = () => {
        const target = String(currentSlideUrl || '').trim();
        if (!target) {
            return false;
        }

        const normalizeForCompare = (value) => String(resolveRenderableImageUrl(value) || value || '')
            .split('#')[0]
            .split('?')[0]
            .trim()
            .replace(/\/+$/, '');

        const targetNormalized = normalizeForCompare(target);
        if (!targetNormalized) {
            return false;
        }

        const dedicatedUrls = Array.isArray(fullScreenSlideUrls) && fullScreenSlideUrls.length > 0
            ? fullScreenSlideUrls
            : parseConfiguredDedicatedFullScreenSlideUrls();

        return dedicatedUrls.some((url) => normalizeForCompare(url) === targetNormalized);
    };

    const compactViewport = isCompactViewport();

    const perImageSettings = getImageSlideSettings(currentSlideUrl);
    const configuredFit = String(perImageSettings?.imageFit || visualConfig.rightSidebarImageFit || 'scale-down').toLowerCase();
    const fit = configuredFit === 'cover'
        ? 'cover'
        : (configuredFit === 'contain' ? 'contain' : 'scale-down');
    const configuredBackgroundColor = String(perImageSettings?.imageBackgroundColor || '#000000').trim() || '#000000';
    tvImageSlide.style.objectFit = fit;
    tvImageSlide.style.objectPosition = 'center center';
    tvImageSlide.style.setProperty('background-color', configuredBackgroundColor, 'important');
    if (tvRightSidebarMediaWrap) {
        tvRightSidebarMediaWrap.style.setProperty('background-color', configuredBackgroundColor, 'important');
    }
    if (tvVideoPanel) {
        tvVideoPanel.style.setProperty('background-color', configuredBackgroundColor, 'important');
    }
    tvImageSlide.style.maxWidth = '100%';
    tvImageSlide.style.maxHeight = '100%';

    const parsedHeight = Number(visualConfig.rightSidebarImageHeight);
    let imgHeight = Number.isFinite(parsedHeight) ? Math.max(0, Math.min(1000, parsedHeight)) : 96;
    const parsedWidth = Number(visualConfig.rightSidebarImageWidth);
    let imgWidth = Number.isFinite(parsedWidth) ? Math.max(0, Math.min(1000, parsedWidth)) : 0;

    const perImageHeight = Math.max(0, Number(perImageSettings?.imageHeight || 0) || 0);
    const perImageWidth = Math.max(0, Number(perImageSettings?.imageWidth || 0) || 0);
    const perImageVerticalOffset = Math.max(-300, Math.min(300, Number(perImageSettings?.verticalOffset || 0) || 0));
    const shouldFullscreen = forceFullScreenSlideModeActive
        && toBoolean(visualConfig.fullScreenSlideEnabled, false)
        && parseConfiguredDedicatedFullScreenSlideUrls().length > 0
        && isCurrentUrlFromDedicatedFullScreenSet();

    setImageSlideFullscreenMode(shouldFullscreen);

    if (shouldFullscreen) {
        const isAndroidRuntime = isAndroidDevice();
        const configuredWidth = isAndroidRuntime
            ? Math.max(0, Number(visualConfig.fullScreenSlideImageWidthAndroid || 0))
            : Math.max(0, Number(visualConfig.fullScreenSlideImageWidthWindows || 0));
        const configuredHeight = isAndroidRuntime
            ? Math.max(0, Number(visualConfig.fullScreenSlideImageHeightAndroid || 0))
            : Math.max(0, Number(visualConfig.fullScreenSlideImageHeightWindows || 0));

        tvImageSlide.style.width = configuredWidth > 0 ? `${configuredWidth}px` : '100vw';
        tvImageSlide.style.height = configuredHeight > 0 ? `${configuredHeight}px` : '100vh';
        tvImageSlide.style.maxWidth = configuredWidth > 0 ? `${configuredWidth}px` : '100vw';
        tvImageSlide.style.maxHeight = configuredHeight > 0 ? `${configuredHeight}px` : '100vh';
        tvImageSlide.style.objectFit = (configuredWidth > 0 || configuredHeight > 0) ? 'contain' : 'cover';
        tvImageSlide.style.objectPosition = 'center center';
        return;
    }

    if (perImageHeight > 0) {
        imgHeight = perImageHeight;
    }

    if (perImageWidth > 0) {
        imgWidth = perImageWidth;
    }

    if (perImageVerticalOffset !== 0) {
        tvImageSlide.style.objectPosition = `center calc(50% + ${perImageVerticalOffset}px)`;
    }

    if (compactViewport) {
        const sidebarLayout = getRightSidebarLayoutDimensions();
        const compactMaxHeight = Math.max(
            140,
            Math.min(
                sidebarLayout.compactMaxHeightPx || 620,
                Math.round(window.innerHeight * 0.9)
            )
        );
        const compactMaxWidth = Math.max(140, Math.min(320, Math.round(window.innerWidth * 0.92)));

        tvImageSlide.style.width = '100%';
        tvImageSlide.style.height = '100%';
        const compactConfiguredWidth = imgWidth > 0 ? Math.max(80, Math.min(compactMaxWidth, imgWidth)) : compactMaxWidth;
        const compactConfiguredHeight = imgHeight > 0 ? Math.max(80, Math.min(compactMaxHeight, imgHeight)) : compactMaxHeight;
        tvImageSlide.style.maxWidth = `${compactConfiguredWidth}px`;
        tvImageSlide.style.maxHeight = `${compactConfiguredHeight}px`;
        tvImageSlide.style.objectFit = fit;
        return;
    }

    // Auto mode still needs a fixed render box, otherwise object-fit has no visual effect.
    if (imgHeight === 0 && imgWidth === 0) {
        tvImageSlide.style.width = '100%';
        tvImageSlide.style.height = '100%';
        tvImageSlide.style.objectFit = fit;
        return;
    }

    if (imgHeight > 0) {
        tvImageSlide.style.height = imgHeight + 'px';
    } else {
        tvImageSlide.style.height = '';
    }

    if (imgWidth > 0) {
        tvImageSlide.style.width = imgWidth + 'px';
    } else {
        tvImageSlide.style.width = '';
    }
}

function startImageSlideMode() {
    if (!tvImageSlide) {
        return;
    }

    stopVideoPlaybackForImageMode();
    stopDedicatedFullScreenSlideMode();
    clearImageSlideTimer();

    applyConfiguredSlideImageLayout();

    imageSlideUrls = parseConfiguredImageSlideUrls();

    if (imageSlideUrls.length === 0) {
        setImageSlideFullscreenMode(false);
        tvImageSlide.classList.add('hidden');
        tvImageSlide.removeAttribute('src');
        hideSlideTextOverlays();
        return;
    }

    tvImageSlide.classList.remove('hidden');
    showImageSlideAt(0);

    const intervalSeconds = Math.max(1, Number(visualConfig.rightSidebarImageInterval || 8));
    if (imageSlideUrls.length > 1) {
        imageSlideTimer = setInterval(() => {
            showImageSlideAt(currentImageSlideIndex + 1);
        }, intervalSeconds * 1000);
    }
}

function stopImageSlideMode() {
    clearImageSlideTimer();
    stopDedicatedFullScreenSlideMode();
    imageSlideUrls = [];
    imageSlideSettingsByUrl = new Map();
    currentImageSlideIndex = 0;
    setImageSlideFullscreenMode(false);

    if (tvImageSlide) {
        tvImageSlide.classList.add('hidden');
        tvImageSlide.removeAttribute('src');
    }

    hideSlideTextOverlays();
}

function clearSidebarProductTimer() {
    if (!sidebarProductTimer) {
        return;
    }

    clearInterval(sidebarProductTimer);
    sidebarProductTimer = null;
}

function ensureSidebarProductCard() {
    if (sidebarProductCardEl) {
        return sidebarProductCardEl;
    }

    if (!tvRightSidebarMediaWrap) {
        return null;
    }

    const card = document.createElement('div');
    card.id = 'tvRightSidebarProductCard';
    card.className = 'hidden';
    card.style.position = 'absolute';
    card.style.inset = '0';
    card.style.display = 'none';
    card.style.flexDirection = 'column';
    card.style.alignItems = 'stretch';
    card.style.justifyContent = 'space-between';
    card.style.padding = '10px';
    card.style.gap = '8px';
    card.style.zIndex = '22';
    card.style.pointerEvents = 'none';

    tvRightSidebarMediaWrap.style.position = 'relative';
    tvRightSidebarMediaWrap.appendChild(card);
    sidebarProductCardEl = card;
    return card;
}

function hideSidebarProductCard() {
    if (!sidebarProductCardEl) {
        return;
    }

    sidebarProductCardEl.style.display = 'none';
    sidebarProductCardEl.classList.add('hidden');
}

function normalizeSidebarProductItems(items) {
    if (!Array.isArray(items)) {
        return [];
    }

    const displayMode = String(visualConfig.rightSidebarProductDisplayMode || 'all').toLowerCase();

    return items.filter((item) => {
        if (displayMode !== 'offers_only') {
            return true;
        }

        const oferta = Number(item?.oferta || 0);
        return Number.isFinite(oferta) && oferta > 0;
    });
}

function renderSidebarProductItem(item) {
    rightSidebarProductCardRendererModule.renderSidebarProductItem(item);
}

function startSidebarProductsOnlyMode(items) {
    rightSidebarProductSequencesModule.startSidebarProductsOnlyMode(items);
}

function startSidebarProductsBeforeImagesMode(items) {
    rightSidebarProductSequencesModule.startSidebarProductsBeforeImagesMode(items);
}

function startSidebarProductsMixedWithImagesMode(items) {
    rightSidebarMixedModeModule.startSidebarProductsMixedWithImagesMode(items);
}

function startSidebarProductsMixedWithMediaMode(items) {
    rightSidebarMixedModeModule.startSidebarProductsMixedWithMediaMode(items);
}

function startSidebarProductsMixedMode(items, includeVideos) {
    rightSidebarMixedModeModule.startSidebarProductsMixedMode(items, includeVideos);
}

function getRightSidebarMediaType() {
    return rightSidebarMediaModeModule.getRightSidebarMediaType();
}

function getHybridVideoCountLimit() {
    return rightSidebarMediaModeModule.getHybridVideoCountLimit();
}

function getHybridImageCountLimit() {
    return rightSidebarMediaModeModule.getHybridImageCountLimit();
}

function getNextHybridPhase(phase) {
    return rightSidebarMediaModeModule.getNextHybridPhase(phase);
}

function applyHybridImageLayout() {
    applyConfiguredSlideImageLayout();
}

async function applyRightSidebarModeOnce(token, mode) {
    await rightSidebarMediaModeModule.applyRightSidebarModeOnce(token, mode);
}

async function switchRightSidebarHybridPhase(token, nextPhase) {
    await rightSidebarMediaModeModule.switchRightSidebarHybridPhase(token, nextPhase);
}

function handleHybridVideoItemCompleted(token) {
    rightSidebarMediaModeModule.handleHybridVideoItemCompleted(token);
}

function startHybridImagePhase(token) {
    if (!tvImageSlide) {
        switchRightSidebarHybridPhase(token, 'video');
        return;
    }

    stopVideoPlaybackForImageMode();
    clearImageSlideTimer();

    applyHybridImageLayout();

    imageSlideUrls = parseConfiguredImageSlideUrls();
    if (imageSlideUrls.length === 0) {
        tvImageSlide.classList.add('hidden');
        tvImageSlide.removeAttribute('src');
        switchRightSidebarHybridPhase(token, 'video');
        return;
    }

    const imageLimit = getHybridImageCountLimit();
    const intervalMs = Math.max(1, Number(visualConfig.rightSidebarImageInterval || 8)) * 1000;

    tvImageSlide.classList.remove('hidden');
    rightSidebarHybridImageCountInPhase = 0;

    const showNextHybridImage = () => {
        if (getRightSidebarMediaType() !== 'hybrid' || rightSidebarHybridPhase !== 'image') {
            return;
        }

        applyConfiguredSlideImageLayout();

        if (!rightSidebarHybridHasShownAnyImage) {
            showImageSlideAt(0);
            rightSidebarHybridHasShownAnyImage = true;
        } else {
            showImageSlideAt(currentImageSlideIndex + 1);
        }

        rightSidebarHybridImageCountInPhase += 1;

        if (rightSidebarHybridImageCountInPhase >= imageLimit) {
            clearImageSlideTimer();
            setTimeout(() => {
                if (getRightSidebarMediaType() !== 'hybrid' || rightSidebarHybridPhase !== 'image') {
                    return;
                }

                switchRightSidebarHybridPhase(token, 'video');
            }, intervalMs);
        }
    };

    showNextHybridImage();

    if (imageLimit > 1) {
        imageSlideTimer = setInterval(showNextHybridImage, intervalMs);
    }
}

async function applyRightSidebarMediaMode(token, productsForSidebar = null, options = {}) {
    await rightSidebarMediaModeModule.applyRightSidebarMediaMode(token, productsForSidebar, options);
}

function scheduleInitialVideoAutoplayRetry(videoItem) {
    clearInitialVideoAutoplayRetryTimer();

    initialVideoAutoplayRetryTimer = setTimeout(() => {
        const currentUrl = String(typeof videoItem === 'object' ? videoItem?.url : videoItem || '').trim();
        if (!currentUrl) {
            return;
        }

        if (tvVideo && !tvVideo.classList.contains('hidden') && tvVideo.paused) {
            const preferredMuted = Boolean(typeof videoItem === 'object' ? videoItem?.muted : visualConfig.videoMuted);
            playHtmlVideoWithAutoplayFallback(preferredMuted);
            return;
        }

        if (tvEmbed && !tvEmbed.classList.contains('hidden')) {
            const videoId = getYouTubeVideoId(currentUrl);
            if (videoId) {
                if (youTubePlayer && typeof youTubePlayer.getPlayerState === 'function' && typeof youTubePlayer.playVideo === 'function') {
                    try {
                        const state = youTubePlayer.getPlayerState();
                        const isPlaying = state === 1 || state === 3;

                        if (!isPlaying) {
                            youTubePlayer.playVideo();
                        }
                    } catch (_error) {
                    }
                }

                return;
            }
        }
    }, 1200);
}

function scheduleVideoFallbackAdvance(durationSeconds) {
    clearVideoFallbackTimer();

    const seconds = Number(durationSeconds || 0);
    if (seconds <= 0 || videoPlaylistItems.length <= 1) {
        return;
    }

    videoFallbackTimer = setTimeout(() => {
        playNextVideoInPlaylist();
    }, seconds * 1000);
}

function clearYouTubeStatePoll() {
    if (youTubeStatePollTimer) {
        clearInterval(youTubeStatePollTimer);
        youTubeStatePollTimer = null;
    }
}

function clearYouTubePlayKeepAlive() {
    if (youTubePlayKeepAliveTimer) {
        clearInterval(youTubePlayKeepAliveTimer);
        youTubePlayKeepAliveTimer = null;
    }
}

function clearYouTubeUnmuteDelay() {
    if (youTubeUnmuteDelayTimer) {
        clearTimeout(youTubeUnmuteDelayTimer);
        youTubeUnmuteDelayTimer = null;
    }
}

function clearYouTubeStartupTimeout() {
    if (youTubeStartupTimeoutTimer) {
        clearTimeout(youTubeStartupTimeoutTimer);
        youTubeStartupTimeoutTimer = null;
    }
}

function getYouTubeErrorMessage(errorCode) {
    const code = Number(errorCode);

    if (code === 2) {
        return 'Erro 2 do YouTube: URL/ID inválido para reprodução.';
    }

    if (code === 5) {
        return 'Erro 5 do YouTube: player HTML5 não conseguiu reproduzir este vídeo.';
    }

    if (code === 100) {
        return 'Erro 100 do YouTube: vídeo removido, privado ou indisponível.';
    }

    if (code === 101 || code === 150) {
        return 'Erro 101/150 do YouTube: incorporação bloqueada pelo proprietário do vídeo.';
    }

    return `Erro ${Number.isFinite(code) ? code : 'desconhecido'} do YouTube: não foi possível reproduzir este vídeo neste dispositivo.`;
}

function getYouTubeStateLabel(state) {
    const parsed = Number(state);

    if (parsed === -1) {
        return 'não iniciado';
    }

    if (parsed === 0) {
        return 'finalizado';
    }

    if (parsed === 1) {
        return 'reproduzindo';
    }

    if (parsed === 2) {
        return 'pausado';
    }

    if (parsed === 3) {
        return 'carregando';
    }

    if (parsed === 5) {
        return 'vídeo sugerido em fila';
    }

    return 'desconhecido';
}

function loadYouTubeApi(callback) {
    if (youTubeApiReady && typeof callback === 'function') {
        callback();
        return;
    }

    if (!youTubeApiLoading) {
        youTubeApiLoading = true;
        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        script.async = true;
        document.head.appendChild(script);
    }

    const previousHandler = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => {
        youTubeApiReady = true;
        if (typeof previousHandler === 'function') {
            previousHandler();
        }
        if (typeof callback === 'function') {
            callback();
        }
    };
}

function bindYouTubeEndedEvent() {
    if (!tvEmbed || typeof window.YT === 'undefined' || typeof window.YT.Player === 'undefined') {
        return;
    }

    clearYouTubeStatePoll();
    clearYouTubePlayKeepAlive();
    clearYouTubeUnmuteDelay();
    clearYouTubeStartupTimeout();

    if (youTubePlayer && typeof youTubePlayer.destroy === 'function') {
        youTubePlayer.destroy();
    }

    youTubePlayerReady = false;

    youTubePlayer = new window.YT.Player('tvEmbed', {
        events: {
            onReady: () => {
                youTubePlayerReady = true;
                clearYouTubeStatePoll();
                youTubeStatePollTimer = setInterval(() => {
                    if (!youTubePlayer || typeof youTubePlayer.getPlayerState !== 'function') {
                        return;
                    }

                    try {
                        const state = youTubePlayer.getPlayerState();

                        if (typeof youTubePlayer.isMuted === 'function') {
                            try {
                                if (!youTubePlayer.isMuted()) {
                                    markAudioAutoplayUnlocked();
                                }
                            } catch (_error) {
                            }
                        }

                        if (state === 0 && videoPlaylistItems.length > 1) {
                            playNextVideoInPlaylist();
                            return;
                        }

                        if (typeof youTubePlayer.getCurrentTime === 'function' && typeof youTubePlayer.getDuration === 'function') {
                            const current = Number(youTubePlayer.getCurrentTime() || 0);
                            const duration = Number(youTubePlayer.getDuration() || 0);

                            if (duration > 0 && current >= Math.max(0, duration - 0.5) && videoPlaylistItems.length > 1) {
                                playNextVideoInPlaylist();
                            }
                        }
                    } catch (_error) {
                    }
                }, 1000);
            },
            onStateChange: (event) => {
                if (event.data === 1 || event.data === 3) {
                    clearYouTubeStartupTimeout();
                }

                if (event.data === 1 && shouldUnmuteYouTubeAfterStart) {
                    clearYouTubeUnmuteDelay();
                    youTubeUnmuteDelayTimer = setTimeout(() => {
                        shouldUnmuteYouTubeAfterStart = false;

                        if (!youTubePlayer || typeof youTubePlayer.unMute !== 'function') {
                            return;
                        }

                        try {
                            youTubePlayer.unMute();
                            if (typeof youTubePlayer.playVideo === 'function') {
                                youTubePlayer.playVideo();
                            }
                            markAudioAutoplayUnlocked();

                            setTimeout(() => {
                                if (!youTubePlayer || typeof youTubePlayer.getPlayerState !== 'function') {
                                    return;
                                }

                                try {
                                    const stateAfterUnmute = youTubePlayer.getPlayerState();
                                    const isPlayingAfterUnmute = stateAfterUnmute === 1 || stateAfterUnmute === 3;

                                    if (isPlayingAfterUnmute) {
                                        return;
                                    }

                                    if (typeof youTubePlayer.mute === 'function') {
                                        youTubePlayer.mute();
                                    }

                                    if (typeof youTubePlayer.playVideo === 'function') {
                                        youTubePlayer.playVideo();
                                    }
                                } catch (_error) {
                                }
                            }, 900);
                        } catch (_error) {
                        }
                    }, 3000);
                }

                if (event.data === 2) {
                    clearYouTubeUnmuteDelay();
                }

                if (event.data === 0 && videoPlaylistItems.length > 1) {
                    clearYouTubeUnmuteDelay();
                    clearYouTubeStartupTimeout();
                    playNextVideoInPlaylist();
                }
            },
            onError: (event) => {
                clearYouTubeUnmuteDelay();
                clearYouTubeStartupTimeout();

                const code = Number(event?.data ?? 0);
                const reason = getYouTubeErrorMessage(code);

                if (videoHint) {
                    videoHint.textContent = reason;
                }

                if (typeof window !== 'undefined') {
                    window.__tvLastYouTubeError = {
                        code,
                        reason,
                        videoId: currentYouTubeVideoId,
                        at: new Date().toISOString(),
                    };
                }

                if (videoPlaylistItems.length > 1) {
                    setTimeout(() => {
                        playNextVideoInPlaylist();
                    }, 1200);
                }
            },
        },
    });
}

function getYouTubeVideoId(url) {
    if (!url) {
        return '';
    }

    try {
        const parsed = new URL(url);
        const host = parsed.hostname.toLowerCase();

        if (host.includes('youtube.com')) {
            const byQuery = parsed.searchParams.get('v');
            if (byQuery) {
                return byQuery;
            }

            const embedMatch = parsed.pathname.match(/\/embed\/([^/?]+)/);
            if (embedMatch?.[1]) {
                return embedMatch[1];
            }

            const liveMatch = parsed.pathname.match(/\/live\/([^/?]+)/);
            if (liveMatch?.[1]) {
                return liveMatch[1];
            }

            const shortsMatch = parsed.pathname.match(/\/shorts\/([^/?]+)/);
            if (shortsMatch?.[1]) {
                return shortsMatch[1];
            }
        }

        if (host.includes('youtu.be')) {
            const byPath = parsed.pathname.replace('/', '').trim();
            if (byPath) {
                return byPath;
            }
        }
    } catch (_error) {
        return '';
    }

    return '';
}

function playYouTubeVideo(videoId, isMuted) {
    if (!videoId) {
        return;
    }

    currentYouTubeVideoId = videoId;
    youTubeRequestedMuted = Boolean(isMuted);
    youTubeStartupMuteRetryDone = false;
    clearYouTubeUnmuteDelay();
    clearYouTubeStartupTimeout();
    shouldUnmuteYouTubeAfterStart = !youTubeRequestedMuted;

    loadYouTubeApi(() => {
        if (!youTubePlayer || !youTubePlayerReady) {
            bindYouTubeEndedEvent();
        }

        const tryLoad = () => {
            if (!youTubePlayer || !youTubePlayerReady) {
                setTimeout(tryLoad, 150);
                return;
            }

            try {
                if (typeof youTubePlayer.loadVideoById === 'function') {
                    youTubePlayer.loadVideoById(videoId);
                }

                if (typeof youTubePlayer.mute === 'function') {
                    try {
                        youTubePlayer.mute();
                    } catch (_error) {
                    }
                }

                if (typeof youTubePlayer.playVideo === 'function') {
                    youTubePlayer.playVideo();
                }

                if (videoPlaylistItems.length > 1) {
                    clearYouTubeStartupTimeout();
                    youTubeStartupTimeoutTimer = setTimeout(() => {
                        if (!youTubePlayer || typeof youTubePlayer.getPlayerState !== 'function') {
                            return;
                        }

                        try {
                            const state = youTubePlayer.getPlayerState();
                            const isPlaying = state === 1 || state === 3;

                            if (!isPlaying) {
                                if (!youTubeStartupMuteRetryDone && !youTubeRequestedMuted) {
                                    youTubeStartupMuteRetryDone = true;

                                    if (typeof youTubePlayer.mute === 'function') {
                                        try {
                                            youTubePlayer.mute();
                                        } catch (_error) {
                                        }
                                    }

                                    if (typeof youTubePlayer.playVideo === 'function') {
                                        try {
                                            youTubePlayer.playVideo();
                                        } catch (_error) {
                                        }
                                    }

                                    clearYouTubeStartupTimeout();
                                    youTubeStartupTimeoutTimer = setTimeout(() => {
                                        if (!youTubePlayer || typeof youTubePlayer.getPlayerState !== 'function') {
                                            return;
                                        }

                                        try {
                                            const stateAfterRetry = youTubePlayer.getPlayerState();
                                            const isPlayingAfterRetry = stateAfterRetry === 1 || stateAfterRetry === 3;
                                            if (!isPlayingAfterRetry) {
                                                if (videoHint) {
                                                    videoHint.textContent = `YouTube não iniciou este vídeo (estado: ${getYouTubeStateLabel(stateAfterRetry)}). Motivo provável: bloqueio de incorporação, restrição regional/idade ou limitação do dispositivo.`;
                                                }

                                                if (typeof window !== 'undefined') {
                                                    window.__tvLastYouTubeError = {
                                                        code: null,
                                                        reason: 'Sem código do YouTube (falha de inicialização sem onError).',
                                                        state: stateAfterRetry,
                                                        stateLabel: getYouTubeStateLabel(stateAfterRetry),
                                                        videoId: currentYouTubeVideoId,
                                                        at: new Date().toISOString(),
                                                    };
                                                }

                                                playNextVideoInPlaylist();
                                            }
                                        } catch (_error) {
                                        }
                                    }, 6000);

                                    return;
                                }

                                if (videoHint) {
                                    videoHint.textContent = `YouTube não iniciou este vídeo (estado: ${getYouTubeStateLabel(state)}). Tentando fallback automático...`;
                                }

                                playNextVideoInPlaylist();
                            }
                        } catch (_error) {
                        }
                    }, 8000);
                }

                clearYouTubePlayKeepAlive();
                let keepAliveAttempts = 0;
                youTubePlayKeepAliveTimer = setInterval(() => {
                    if (!youTubePlayer || typeof youTubePlayer.getPlayerState !== 'function' || typeof youTubePlayer.playVideo !== 'function') {
                        return;
                    }

                    try {
                        const state = youTubePlayer.getPlayerState();
                        const isPlaying = state === 1 || state === 3;

                        if (!isPlaying) {
                            youTubePlayer.playVideo();
                        }
                    } catch (_error) {
                    }

                    keepAliveAttempts += 1;
                    if (keepAliveAttempts >= 5) {
                        clearYouTubePlayKeepAlive();
                    }
                }, 1200);
            } catch (_error) {
            }
        };

        tryLoad();
    });
}

function ensureVideoAudioEnabled(isMuted = false) {
    if (!tvVideo) {
        return;
    }

    tvVideo.muted = Boolean(isMuted);

    if (!isMuted && tvVideo.volume === 0) {
        tvVideo.volume = 1;
    }
}

async function playHtmlVideoWithAutoplayFallback(preferredMuted = false) {
    if (!tvVideo) {
        return;
    }

    ensureVideoAudioEnabled(preferredMuted);

    try {
        await tvVideo.play();
        if (!tvVideo.muted) {
            markAudioAutoplayUnlocked();
        }
        return;
    } catch (_error) {
    }

    tvVideo.muted = true;

    try {
        await tvVideo.play();

        if (!preferredMuted && audioAutoplayUnlocked) {
            setTimeout(() => {
                tvVideo.muted = false;
                if (tvVideo.volume === 0) {
                    tvVideo.volume = 1;
                }
            }, 300);
        }
    } catch (_error) {
    }
}

function resolveEmbedUrl(url, isMuted = false) {
    if (!url) {
        return null;
    }

    try {
        const parsed = new URL(url);
        const host = parsed.hostname.toLowerCase();
        const origin = encodeURIComponent(window.location.origin);

        if (host.includes('youtube.com')) {
            let videoId = parsed.searchParams.get('v');

            if (!videoId) {
                const embedMatch = parsed.pathname.match(/\/embed\/([^/?]+)/);
                if (embedMatch?.[1]) {
                    videoId = embedMatch[1];
                }
            }

            if (!videoId) {
                const liveMatch = parsed.pathname.match(/\/live\/([^/?]+)/);
                if (liveMatch?.[1]) {
                    videoId = liveMatch[1];
                }
            }

            if (videoId) {
                const muteParam = isMuted ? 1 : 0;
                return `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=${muteParam}&enablejsapi=1&playsinline=1&origin=${origin}`;
            }

            const shortsMatch = parsed.pathname.match(/\/shorts\/([^/?]+)/);
            if (shortsMatch?.[1]) {
                const shortsId = shortsMatch[1];
                const muteParam = isMuted ? 1 : 0;
                return `https://www.youtube.com/embed/${shortsId}?autoplay=1&mute=${muteParam}&enablejsapi=1&playsinline=1&origin=${origin}`;
            }
        }

        if (host.includes('youtu.be')) {
            const videoId = parsed.pathname.replace('/', '');
            if (videoId) {
                const muteParam = isMuted ? 1 : 0;
                return `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=${muteParam}&enablejsapi=1&playsinline=1&origin=${origin}`;
            }
        }

        if (host.includes('drive.google.com')) {
            const match = parsed.pathname.match(/\/file\/d\/([^/]+)/);
            if (match?.[1]) {
                return `https://drive.google.com/file/d/${match[1]}/preview`;
            }
        }
    } catch (_error) {
        return null;
    }

    return null;
}

function parseConfiguredVideoUrls() {
    if (Array.isArray(visualConfig.videoPlaylist) && visualConfig.videoPlaylist.length > 0) {
        return visualConfig.videoPlaylist
            .map((item) => ({
                url: extractVideoUrl(String(item?.url || '').trim()),
                muted: toBoolean(item?.muted, false),
                active: toBoolean(item?.active, true),
                durationSeconds: Math.max(0, Number(item?.durationSeconds || 0)),
                heightPx: Math.max(0, Number(item?.heightPx || 0)),
            }))
            .filter((item) => item.url !== '' && item.active);
    }

    const raw = String(visualConfig.videoUrl || '').trim();

    if (!raw) {
        return [];
    }

    return raw
        .split(/\r?\n|,|;\s*/)
        .map((item) => extractVideoUrl(item.trim()))
        .filter(Boolean)
        .map((url) => ({ url, muted: Boolean(visualConfig.videoMuted), active: true, durationSeconds: 0, heightPx: 0 }));
}

function extractVideoUrl(input) {
    const value = String(input || '').trim();
    if (!value) {
        return '';
    }

    const srcMatch = value.match(/src=["']([^"']+)["']/i);
    if (srcMatch?.[1]) {
        return srcMatch[1].trim();
    }

    const httpMatch = value.match(/https?:\/\/[^\s"'<>]+/i);
    if (httpMatch?.[0]) {
        return httpMatch[0].trim();
    }

    return value;
}

function applyCurrentVideoHeight(heightPx = 0) {
    const resolvedHeight = Math.max(0, Number(heightPx || 0));

    if (tvVideo) {
        tvVideo.style.height = resolvedHeight > 0 ? `${resolvedHeight}px` : '';
    }

    if (tvEmbed) {
        tvEmbed.style.height = resolvedHeight > 0 ? `${resolvedHeight}px` : '';
        tvEmbed.style.aspectRatio = resolvedHeight > 0 ? 'auto' : '';
    }
}

function applyVideoSource(videoItem) {
    const videoUrl = (typeof videoItem === 'string' ? videoItem : videoItem?.url) || '';
    const itemMuted = toBoolean(typeof videoItem === 'object' ? videoItem?.muted : visualConfig.videoMuted, false);
    const shouldForceFirstPlaybackMuted = forceMuteOnFirstPlayback && Boolean(videoUrl);
    const effectiveMuted = shouldForceFirstPlaybackMuted ? true : itemMuted;
    const itemDurationSeconds = Math.max(0, Number(typeof videoItem === 'object' ? videoItem?.durationSeconds : 0) || 0);
    const itemHeightPx = Math.max(0, Number(typeof videoItem === 'object' ? videoItem?.heightPx : 0) || 0);
    const youTubeVideoId = getYouTubeVideoId(videoUrl);
    const embedUrl = resolveEmbedUrl(videoUrl, effectiveMuted);

    if (shouldForceFirstPlaybackMuted) {
        forceMuteOnFirstPlayback = false;
    }

    clearVideoFallbackTimer();
    document.body.classList.remove('tv-video-fullscreen');
    applyCurrentVideoHeight(itemHeightPx);

    if (!videoUrl) {
        if (tvVideo) {
            tvVideo.classList.remove('hidden');
            const source = tvVideo.querySelector('source');
            if (source && !source.src) {
                source.src = '/tv/videos/demo.mp4';
                tvVideo.load();
            }
            ensureVideoAudioEnabled(false);
        }
        if (tvEmbed) {
            tvEmbed.classList.add('hidden');
            tvEmbed.removeAttribute('src');
        }
        videoPlaylistItems = [];
        currentVideoIndex = 0;
        currentYouTubeVideoId = '';
        return;
    }

    if (youTubeVideoId && tvEmbed) {
        if (tvVideo) {
            tvVideo.classList.add('hidden');
            tvVideo.pause();
        }

            const youTubeEmbedUrl = resolveEmbedUrl(videoUrl, effectiveMuted);
        if (youTubeEmbedUrl) {
            tvEmbed.src = youTubeEmbedUrl;
        }

        tvEmbed.classList.remove('hidden');
            playYouTubeVideo(youTubeVideoId, effectiveMuted);

        scheduleInitialVideoAutoplayRetry(videoItem);
        scheduleVideoFallbackAdvance(itemDurationSeconds);

        if (videoHint) {
            videoHint.textContent = 'Vídeo carregado a partir do link configurado.';
        }
        return;
    }

    if (embedUrl && tvEmbed) {
        if (tvVideo) {
            tvVideo.classList.add('hidden');
            tvVideo.pause();
        }

        tvEmbed.src = embedUrl;
        tvEmbed.classList.remove('hidden');
        clearYouTubeStatePoll();
        scheduleInitialVideoAutoplayRetry(videoItem);

        if (videoHint) {
            videoHint.textContent = 'Vídeo carregado a partir do link configurado.';
        }

        scheduleVideoFallbackAdvance(itemDurationSeconds);

        if (videoPlaylistItems.length > 1 && !embedUrl.includes('youtube.com/embed')) {
            videoHint.textContent = 'Links embed não-YouTube não informam término automático para avançar sequência.';
        }
        return;
    }

    if (tvVideo) {
        currentYouTubeVideoId = '';
        clearYouTubeStatePoll();
        const source = tvVideo.querySelector('source');
        if (source) {
            source.src = videoUrl;
            tvVideo.load();
            playHtmlVideoWithAutoplayFallback(effectiveMuted);
        }
        tvVideo.classList.remove('hidden');
        scheduleInitialVideoAutoplayRetry(videoItem);
        scheduleVideoFallbackAdvance(itemDurationSeconds);
    }

    if (tvEmbed) {
        tvEmbed.classList.add('hidden');
        tvEmbed.removeAttribute('src');
    }

    if (videoHint) {
        videoHint.textContent = 'Vídeo carregado a partir do link configurado.';
    }
}

function playNextVideoInPlaylist() {
    if (!Array.isArray(videoPlaylistItems) || videoPlaylistItems.length === 0) {
        return;
    }

    const now = Date.now();
    if (now - lastVideoAdvanceAt < 1000) {
        return;
    }

    lastVideoAdvanceAt = now;
    clearVideoFallbackTimer();

    const token = getReliableDeviceToken();
    if (token) {
        handleHybridVideoItemCompleted(token);

        if (getRightSidebarMediaType() === 'hybrid' && (rightSidebarHybridPhase === 'image' || rightSidebarHybridSwitching)) {
            return;
        }
    }

    if (currentVideoIndex < videoPlaylistItems.length - 1) {
        currentVideoIndex += 1;
    } else {
        currentVideoIndex = 0;
    }

    applyVideoSource(videoPlaylistItems[currentVideoIndex]);
}

function startVideoPlaylist(videoUrls) {
    const list = (videoUrls || [])
        .map((item) => {
            if (typeof item === 'string') {
                return { url: item, muted: toBoolean(visualConfig.videoMuted, false) };
            }

            return {
                url: String(item?.url || '').trim(),
                muted: toBoolean(item?.muted, false),
                active: toBoolean(item?.active, true),
                durationSeconds: Math.max(0, Number(item?.durationSeconds || 0)),
                heightPx: Math.max(0, Number(item?.heightPx || 0)),
            };
        })
        .filter((item) => item.url !== '' && item.active);

    const nextSignature = JSON.stringify(list.map((item) => ({
        url: item.url,
        muted: item.muted,
        active: item.active,
        durationSeconds: item.durationSeconds,
        heightPx: item.heightPx,
    })));

    if (nextSignature === currentVideoPlaylistSignature && videoPlaylistItems.length > 0 && !forceVideoPlaylistApplyOnce) {
        return;
    }

    if (nextSignature === currentVideoPlaylistSignature && videoPlaylistItems.length > 0 && forceVideoPlaylistApplyOnce) {
        forceVideoPlaylistApplyOnce = false;
        applyVideoSource(videoPlaylistItems[currentVideoIndex] || videoPlaylistItems[0]);
        return;
    }

    if (list.length === 0) {
        applyVideoSource('');
        currentVideoPlaylistSignature = '';
        return;
    }

    videoPlaylistItems = list;
    currentVideoPlaylistSignature = nextSignature;
    currentVideoIndex = 0;
    applyVideoSource(videoPlaylistItems[currentVideoIndex]);
}

async function loadVideoPlaylist(token) {
    const configuredUrls = parseConfiguredVideoUrls();

    if (configuredUrls.length > 0) {
        startVideoPlaylist(configuredUrls);
        return;
    }

    try {
        const response = await fetch(mediaEndpoint, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        const payload = await response.json();
        const apiVideoUrls = (payload?.videos || [])
            .map((item) => item?.conteudo)
            .filter(Boolean);

        startVideoPlaylist(apiVideoUrls);
    } catch (_error) {
        startVideoPlaylist([]);
    }
}

if (initialToken) {
    persistReliableDeviceToken(initialToken);
} else if (window.location.pathname !== configPageUrl) {
    redirectToConfigPage();
}

if (tokenInput) {
    tokenInput.value = initialToken;
}

function formatPrice(value) {
    if (typeof value !== 'number') {
        return '-';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function updateStatus(message, isError = false) {
    if (!statusMessage) {
        return;
    }

    if (statusMessage.__hideTimer) {
        clearTimeout(statusMessage.__hideTimer);
        statusMessage.__hideTimer = null;
    }

    statusMessage.textContent = message;
    statusMessage.className = `tv-status-floating text-xs ${isError ? 'text-red-400' : 'text-slate-400'}`;
    statusMessage.style.display = '';

    if (!isError) {
        statusMessage.__hideTimer = setTimeout(() => {
            if (!statusMessage) {
                return;
            }

            statusMessage.style.display = 'none';
            statusMessage.__hideTimer = null;
        }, 5000);
    }
}

function updateWarningStatus(message) {
    if (!statusMessage) {
        return;
    }

    if (statusMessage.__hideTimer) {
        clearTimeout(statusMessage.__hideTimer);
        statusMessage.__hideTimer = null;
    }

    statusMessage.textContent = message;
    statusMessage.className = 'tv-status-floating text-xs text-amber-300';
    statusMessage.style.display = '';
}

function ensureOfflineIndicator() {
    if (offlineIndicatorEl) {
        return offlineIndicatorEl;
    }

    const indicator = document.createElement('div');
    indicator.id = 'tvOfflineIndicator';
    indicator.textContent = '!';
    indicator.style.position = 'fixed';
    indicator.style.left = '8px';
    indicator.style.top = '50%';
    indicator.style.transform = 'translateY(-50%)';
    indicator.style.zIndex = '95';
    indicator.style.width = '22px';
    indicator.style.height = '22px';
    indicator.style.borderRadius = '999px';
    indicator.style.display = 'none';
    indicator.style.alignItems = 'center';
    indicator.style.justifyContent = 'center';
    indicator.style.fontWeight = '900';
    indicator.style.fontSize = '15px';
    indicator.style.lineHeight = '1';
    indicator.style.color = '#fee2e2';
    indicator.style.background = '#dc2626';
    indicator.style.border = '1px solid #7f1d1d';
    indicator.style.boxShadow = '0 0 0 2px rgba(127, 29, 29, 0.25)';
    indicator.style.pointerEvents = 'none';
    indicator.setAttribute('aria-hidden', 'true');
    document.body.appendChild(indicator);
    offlineIndicatorEl = indicator;
    return indicator;
}

function setOfflineIndicatorVisible(visible) {
    const indicator = ensureOfflineIndicator();
    indicator.style.display = visible ? 'flex' : 'none';
}

function readCachedVisualConfig() {
    try {
        const raw = localStorage.getItem(VISUAL_CONFIG_CACHE_KEY);
        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object' || !parsed.data || typeof parsed.data !== 'object') {
            return null;
        }

        return parsed.data;
    } catch (_error) {
        return null;
    }
}

function saveCachedVisualConfig(data) {
    try {
        localStorage.setItem(VISUAL_CONFIG_CACHE_KEY, JSON.stringify({
            savedAt: Date.now(),
            data,
        }));
    } catch (_error) {
    }
}

function readCachedProducts() {
    try {
        const raw = localStorage.getItem(PRODUCTS_CACHE_KEY);
        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object' || !Array.isArray(parsed.items)) {
            return null;
        }

        return parsed.items;
    } catch (_error) {
        return null;
    }
}

function saveCachedProducts(items) {
    try {
        localStorage.setItem(PRODUCTS_CACHE_KEY, JSON.stringify({
            savedAt: Date.now(),
            items: Array.isArray(items) ? items : [],
        }));
    } catch (_error) {
    }
}

function renderCachedProductsIfAny() {
    const cachedItems = readCachedProducts();
    if (!Array.isArray(cachedItems)) {
        return false;
    }

    renderProductsWithPagination(cachedItems);
    return true;
}

async function applyCachedVisualConfigIfAny() {
    const cachedConfig = readCachedVisualConfig();
    if (!cachedConfig) {
        return false;
    }

    Object.assign(visualConfig, cachedConfig);
    document.body.style.backgroundColor = visualConfig.appBackgroundColor || '#020617';
    const imageUrl = String(visualConfig.backgroundImageUrl || '').trim();
    if (visualConfig.showBackgroundImage && imageUrl) {
        document.body.style.backgroundImage = `url("${imageUrl}")`;
        document.body.style.backgroundRepeat = 'no-repeat';
        document.body.style.backgroundPosition = 'center center';
        document.body.style.backgroundSize = 'cover';
    } else {
        document.body.style.backgroundImage = 'none';
        document.body.style.backgroundRepeat = '';
        document.body.style.backgroundPosition = '';
        document.body.style.backgroundSize = '';
    }

    applyProductsPanelBackground();
    applyProductListVerticalOffset();
    applyVideoBackground();
    applyRightSidebarBorder();
    applyGeneralBorder();
    applyRightSidebarPanelVisibility();
    applyCompactRightSidebarLayout();
    applyLeftVerticalLogoVisibility();
    applyTitleVisibility();
    await applyAutoFullscreenPreference();
    return true;
}

function redirectToConfigPage() {
    try {
        window.location.replace(configPageUrl);
        return;
    } catch (_error) {
    }

    try {
        window.location.assign(configPageUrl);
        return;
    } catch (_error) {
    }

    window.location.href = configPageUrl;
}

function redirectToConfigKeepingTokenHistory(reason = 'Token invalido. Informe um novo token.') {
    // Preserve latest token for quick recovery after network/auth instabilities.
    try {
        const token = String(getReliableDeviceToken() || '').trim();
        if (token) {
            persistReliableDeviceToken(token);
        }
    } catch (_error) {
    }

    updateStatus(reason, true);

    setTimeout(() => {
        redirectToConfigPage();
    }, 200);
}

function shouldForceReconfigureFromPayload(payload) {
    if (!payload || typeof payload !== 'object') {
        return false;
    }

    if (payload.forceReconfigure === true) {
        return true;
    }

    const reason = String(payload.reason || '').toLowerCase();
    return reason === 'device_inactive' || reason === 'device_not_found';
}

function reportNonCriticalAuthWarning(context, payload = null) {
    const now = Date.now();
    if (now - lastNonCriticalAuthWarningAt < 15000) {
        return;
    }

    lastNonCriticalAuthWarningAt = now;
    const reason = String(payload?.reason || 'unauthorized').toLowerCase();
    updateWarningStatus('Aviso: autenticacao temporaria detectada. Mantendo tela ativa.');

    try {
        console.warn('[TV] 401 nao critico mantido sem reconfigurar token.', {
            context,
            reason,
            payload,
        });
    } catch (_error) {
    }
}

async function enforceValidTokenOrRedirect() {
    const token = getReliableDeviceToken();

    if (!token) {
        clearPaginationTimer();
        redirectToConfigPage();
        return false;
    }

    try {
        const response = await fetch(configEndpoint, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (_error) {
            payload = null;
        }

        if (response.status === 401 && shouldForceReconfigureFromPayload(payload)) {
            redirectToConfigKeepingTokenHistory('Token da TV desativado ou removido no admin. Configure um novo token.');
            return false;
        }

        if (response.status === 401) {
            reportNonCriticalAuthWarning('enforceValidTokenOrRedirect', payload);
        }
    } catch (_error) {
        // Keep the current screen when API is temporarily unavailable.
    }

    return true;
}

function setFullscreenTestButtonState() {
    if (!fullscreenTestButton) {
        return;
    }

    const isActive = Boolean(getCurrentFullscreenElement());
    fullscreenTestButton.textContent = '•';
    fullscreenTestButton.title = isActive ? 'Sair da tela cheia' : 'Entrar em tela cheia';
    fullscreenTestButton.setAttribute('aria-label', isActive ? 'Sair da tela cheia' : 'Entrar em tela cheia');
}

function isTypingElement(element) {
    if (!element) {
        return false;
    }

    const tagName = String(element.tagName || '').toLowerCase();
    return element.isContentEditable || tagName === 'input' || tagName === 'textarea' || tagName === 'select';
}

async function toggleFullscreenByUserAction() {
    if (!isFullscreenSupported()) {
        updateStatus('Este navegador nao suporta tela cheia.', true);
        return;
    }

    try {
        if (getCurrentFullscreenElement()) {
            await exitDocumentFullscreen();
            updateStatus('Tela cheia desativada.');
            setFullscreenTestButtonState();
            return;
        }

        window.abrirTelaCheia();
    } catch (_error) {
        updateStatus('Nao foi possivel alternar tela cheia.', true);
    }
}

function applyTitleVisibility() {
    if (!tvHeader) {
        return;
    }

    const showTitle = toBoolean(visualConfig.showTitle, true);
    const resolvedTitle = String(visualConfig.titleText || '').trim() || 'Lista de Produtos (TV)';
    const titlePosition = String(visualConfig.titlePosition || 'top').toLowerCase() === 'footer' ? 'footer' : 'top';
    const dynamic = showTitle && toBoolean(visualConfig.isTitleDynamic, false);
    const titleFontSize = Math.min(96, Math.max(10, Number(visualConfig.titleFontSize || 32)));
    const titleFontFamily = resolveTitleFontFamily(String(visualConfig.titleFontFamily || 'arial'));
    const titleTextColor = String(visualConfig.titleTextColor || '#f8fafc');
    const titleBackgroundColor = toBoolean(visualConfig.isTitleBackgroundTransparent, false)
        ? 'transparent'
        : (visualConfig.titleBackgroundColor || '#0f172a');
    const showTitleBorder = toBoolean(visualConfig.showTitleBorder, true);

    const applyTitleContainerStyle = (element) => {
        if (!element) {
            return;
        }

        element.classList.toggle('border', showTitleBorder);
        element.classList.toggle('border-slate-800', showTitleBorder);

        if (showTitleBorder) {
            element.style.border = '';
            element.style.borderStyle = '';
            element.style.borderWidth = '';
            element.style.borderColor = '';
            return;
        }

        element.style.border = 'none';
        element.style.borderStyle = 'none';
        element.style.borderWidth = '0';
        element.style.borderColor = 'transparent';
    };

    const applyTitleElement = (element, isVisible) => {
        if (!element) {
            return;
        }

        element.textContent = resolvedTitle;
        element.style.fontSize = `${titleFontSize}px`;
        element.style.fontFamily = titleFontFamily;
        element.style.color = titleTextColor;

        if (dynamic && isVisible) {
            element.classList.add('is-dynamic');
            const durationSeconds = Math.min(45, Math.max(8, Math.ceil(resolvedTitle.length * 0.45)));
            element.style.animationDuration = `${durationSeconds}s`;
        } else {
            element.classList.remove('is-dynamic');
            element.style.animationDuration = '';
        }
    };

    const showOnTop = showTitle && titlePosition === 'top';
    const showOnFooter = showTitle && titlePosition === 'footer';

    tvHeader.style.display = showOnTop ? 'block' : 'none';
    tvHeader.style.backgroundColor = titleBackgroundColor;
    applyTitleContainerStyle(tvHeader);
    if (tvFooter) {
        tvFooter.classList.toggle('hidden', !showOnFooter);
        tvFooter.style.display = showOnFooter ? 'block' : 'none';
        tvFooter.style.backgroundColor = titleBackgroundColor;
        applyTitleContainerStyle(tvFooter);
    }

    if (!tvHeaderTitle && !tvFooterTitle) {
        return;
    }

    applyTitleElement(tvHeaderTitle, showOnTop);
    applyTitleElement(tvFooterTitle, showOnFooter);
}

function applyGeneralBorder() {
    if (!tvShell) {
        return;
    }

    const width = Math.min(40, Math.max(0, Number(visualConfig.mainBorderWidth ?? 1)));
    const enabled = toBoolean(visualConfig.isMainBorderEnabled, false) && width > 0;
    const hasRoundedCorners = toBoolean(visualConfig.isRoundedCornersEnabled, true);

    tvShell.style.boxSizing = 'border-box';
    tvShell.style.borderRadius = hasRoundedCorners ? '0.75rem' : '0';

    if (!enabled) {
        tvShell.style.borderStyle = 'none';
        tvShell.style.borderWidth = '0';
        tvShell.style.borderColor = 'transparent';
        return;
    }

    tvShell.style.borderStyle = 'solid';
    tvShell.style.borderWidth = `${width}px`;
    tvShell.style.borderColor = visualConfig.mainBorderColor || '#000000';
}

function getRowCornerRadius() {
    return toBoolean(visualConfig.isRowRoundedEnabled, false) ? '0.75rem' : '0';
}

function resolveGroupLabelFontFamily() {
    const families = {
        arial: 'Arial, sans-serif',
        verdana: 'Verdana, sans-serif',
        tahoma: 'Tahoma, sans-serif',
        trebuchet: 'Trebuchet MS, sans-serif',
        georgia: 'Georgia, serif',
        courier: 'Courier New, monospace',
        system: 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
    };

    const key = String(visualConfig.groupLabelFontFamily || 'arial').toLowerCase();
    return families[key] || families.arial;
}

function resolveTitleFontFamily(fontKey) {
    const families = {
        arial: 'Arial, sans-serif',
        verdana: 'Verdana, sans-serif',
        tahoma: 'Tahoma, sans-serif',
        trebuchet: 'Trebuchet MS, sans-serif',
        georgia: 'Georgia, serif',
        courier: 'Courier New, monospace',
        system: 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
    };

    const key = String(fontKey || 'arial').toLowerCase();
    return families[key] || families.arial;
}

function applyGroupLabelStyles(element) {
    if (!element) {
        return;
    }

    const compactViewport = isCompactViewport();
    const compactScale = getCompactListScale();
    const groupLabelFontSize = Math.min(60, Math.max(10, Number(visualConfig.groupLabelFontSize || 14)));
    const effectiveGroupLabelFontSize = compactViewport
        ? Math.max(10, Math.round(groupLabelFontSize * compactScale))
        : groupLabelFontSize;
    element.style.fontSize = `${effectiveGroupLabelFontSize}px`;
    element.style.color = visualConfig.groupLabelColor || '#cbd5e1';
    element.style.fontFamily = resolveGroupLabelFontFamily();
    element.style.lineHeight = compactViewport ? '1.25' : '1.35';
    element.style.whiteSpace = 'normal';
    element.style.wordBreak = 'break-word';
    element.style.overflowWrap = 'anywhere';
    const groupLabelVerticalOffset = Math.max(-300, Math.min(300, Number(visualConfig.groupLabelVerticalOffset ?? 0)));
    element.style.marginTop = `${groupLabelVerticalOffset}px`;

    if (toBoolean(visualConfig.showGroupLabelBadge, false)) {
        element.style.display = 'inline-block';
        element.style.backgroundColor = visualConfig.groupLabelBadgeColor || '#0f172a';
        const badgeVerticalPadding = compactViewport ? 3 : 4;
        const badgeHorizontalPadding = compactViewport ? 8 : 10;
        element.style.padding = `${badgeVerticalPadding}px ${badgeHorizontalPadding}px`;
        element.style.borderRadius = '6px';
    } else {
        element.style.display = '';
        element.style.backgroundColor = 'transparent';
        element.style.padding = '0';
        element.style.borderRadius = '0';
    }
}

function applyProductsPanelBackground() {
    if (!tvProductsPanel) {
        return;
    }

    if (visualConfig.isProductsPanelTransparent) {
        tvProductsPanel.classList.remove('bg-slate-900');
        tvProductsPanel.style.backgroundColor = 'transparent';
        tvProductsPanel.style.backgroundImage = 'none';
    } else {
        tvProductsPanel.classList.remove('bg-slate-900');
        tvProductsPanel.style.backgroundColor = visualConfig.productsPanelBackgroundColor || '#0f172a';
        tvProductsPanel.style.backgroundImage = 'none';
    }

    const listBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.listBorderWidth ?? 1)));
    const shouldShowListBorder = !visualConfig.isListBorderTransparent && listBorderWidth > 0;

    if (!shouldShowListBorder) {
        tvProductsPanel.style.setProperty('border-width', '0', 'important');
        tvProductsPanel.style.setProperty('border-style', 'none', 'important');
        tvProductsPanel.style.setProperty('border-color', 'transparent', 'important');
        return;
    }

    tvProductsPanel.style.setProperty('border-style', 'solid', 'important');
    tvProductsPanel.style.setProperty('border-width', `${listBorderWidth}px`, 'important');
    tvProductsPanel.style.setProperty('border-color', visualConfig.listBorderColor || '#334155', 'important');
}

function applyProductListVerticalOffset() {
    if (!tvProductsPanel) {
        return;
    }

    const offset = Math.max(-300, Math.min(300, Number(visualConfig.productListVerticalOffset ?? 0)));
    tvProductsPanel.style.marginTop = `${offset}px`;
}

function applyVideoBackground() {
    const color = visualConfig.videoBackgroundColor || '#000000';
    const resolvedColor = visualConfig.isVideoPanelTransparent ? 'transparent' : color;

    const isImageSlideVisible = Boolean(tvImageSlide && !tvImageSlide.classList.contains('hidden'));

    if (!isImageSlideVisible && tvVideoPanel) {
        tvVideoPanel.style.backgroundColor = resolvedColor;
    }

    if (tvVideo) {
        tvVideo.style.backgroundColor = resolvedColor;
    }

    if (tvEmbed) {
        tvEmbed.style.backgroundColor = resolvedColor;
    }

    if (!isImageSlideVisible && tvRightSidebarMediaWrap) {
        tvRightSidebarMediaWrap.style.backgroundColor = resolvedColor;
    }

    if (!isImageSlideVisible && tvImageSlide) {
        tvImageSlide.style.backgroundColor = resolvedColor;
    }
}

function applyRightSidebarBorder() {
    if (!tvVideoPanel) {
        return;
    }

    const rightSidebarBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rightSidebarBorderWidth ?? 1)));
    const shouldShowRightSidebarBorder = toBoolean(visualConfig.showRightSidebarBorder, true) && rightSidebarBorderWidth > 0;

    tvVideoPanel.classList.toggle('border', shouldShowRightSidebarBorder);
    tvVideoPanel.classList.toggle('border-slate-800', shouldShowRightSidebarBorder);

    if (!shouldShowRightSidebarBorder) {
        tvVideoPanel.style.setProperty('border', 'none', 'important');
        tvVideoPanel.style.setProperty('border-width', '0', 'important');
        tvVideoPanel.style.setProperty('border-style', 'none', 'important');
        tvVideoPanel.style.setProperty('border-color', 'transparent', 'important');
        return;
    }

    tvVideoPanel.style.setProperty('border-style', 'solid', 'important');
    tvVideoPanel.style.setProperty('border-width', `${rightSidebarBorderWidth}px`, 'important');
    tvVideoPanel.style.setProperty('border-color', visualConfig.rightSidebarBorderColor || '#334155', 'important');
}

function applyRightSidebarPanelVisibility() {
    if (!tvVideoPanel || !tvMain) {
        return;
    }

    if (!toBoolean(visualConfig.showRightSidebarPanel, true)) {
        stopImageSlideMode();
        stopVideoPlaybackForImageMode();
        tvVideoPanel.style.display = 'none';
        tvMain.style.gridTemplateColumns = 'minmax(0, 1fr)';
        tvMain.style.gridTemplateRows = '1fr';
        if (tvProductsPanel) {
            tvProductsPanel.style.width = '100%';
            tvProductsPanel.style.maxWidth = '100%';
        }
        applyRightSidebarLogoVisibility();
        return;
    }

    tvVideoPanel.style.display = '';
    tvMain.style.gridTemplateColumns = '';
    tvMain.style.gridTemplateRows = '';
    if (tvProductsPanel) {
        tvProductsPanel.style.width = '';
        tvProductsPanel.style.maxWidth = '';
    }
    applyCompactRightSidebarLayout();
    applyRightSidebarLogoVisibility();
}

function applyCompactRightSidebarLayout() {
    const compactViewport = isCompactViewport();
    const compactSidebarActive = isCompactSidebarActive();
    const showRightSidebarPanel = toBoolean(visualConfig.showRightSidebarPanel, true);
    const isAndroid = isAndroidDevice();
    const sidebarLayout = getRightSidebarLayoutDimensions();

    if (!tvMain || !tvVideoPanel || !tvRightSidebarMediaWrap || !tvProductsPanel) {
        return;
    }

    if (!compactViewport) {
        if (showRightSidebarPanel) {
            tvMain.style.gridTemplateColumns = '';
            tvMain.style.gridTemplateRows = '';
        } else {
            tvMain.style.gridTemplateColumns = 'minmax(0, 1fr)';
            tvMain.style.gridTemplateRows = '1fr';
        }
        tvVideoPanel.style.order = '';
        tvVideoPanel.style.padding = '';
        tvVideoPanel.style.gap = '';
        tvVideoPanel.style.marginTop = '';
        tvVideoPanel.style.width = '';
        tvVideoPanel.style.maxWidth = '';
        tvVideoPanel.style.minHeight = '';
        tvVideoPanel.style.maxHeight = '';
        tvVideoPanel.style.height = '';
        tvRightSidebarMediaWrap.style.minHeight = '';
        tvRightSidebarMediaWrap.style.maxHeight = '';
        tvRightSidebarMediaWrap.style.height = '';
        tvProductsPanel.style.padding = '';
        tvProductsPanel.style.width = showRightSidebarPanel ? '' : '100%';
        tvProductsPanel.style.maxWidth = showRightSidebarPanel ? '' : '100%';

        [tvVideo, tvEmbed, tvImageSlide].forEach((element) => {
            if (!element) {
                return;
            }

            element.style.minHeight = '';
            element.style.maxHeight = '';
            element.style.height = '';
        });

        return;
    }

    if (compactSidebarActive) {
        tvMain.style.gridTemplateColumns = `minmax(0, 1fr) ${sidebarLayout.compactWidthPx}px`;
        tvMain.style.gridTemplateRows = '1fr';
    } else {
        tvMain.style.gridTemplateColumns = '1fr';
        tvMain.style.gridTemplateRows = showRightSidebarPanel ? 'auto auto' : '1fr';
    }
    tvVideoPanel.style.order = '';
    tvVideoPanel.style.padding = '10px';
    tvVideoPanel.style.gap = isAndroid ? '6px' : '10px';
    const androidVerticalOffset = isAndroid
        ? Math.max(-300, Math.min(300, Number(visualConfig.rightSidebarAndroidVerticalOffset || 0)))
        : 0;
    tvVideoPanel.style.marginTop = compactSidebarActive && androidVerticalOffset !== 0
        ? `${androidVerticalOffset}px`
        : '';
    tvVideoPanel.style.width = compactSidebarActive ? `${sidebarLayout.compactWidthPx}px` : (showRightSidebarPanel ? '100%' : '');
    tvVideoPanel.style.maxWidth = compactSidebarActive ? `${sidebarLayout.compactWidthPx}px` : (showRightSidebarPanel ? '100%' : '');
    tvVideoPanel.style.minHeight = compactSidebarActive ? `${sidebarLayout.compactMaxHeightPx}px` : '';
    tvVideoPanel.style.maxHeight = compactSidebarActive ? `${sidebarLayout.compactMaxHeightPx}px` : '';
    tvVideoPanel.style.height = compactSidebarActive ? `${sidebarLayout.compactMaxHeightPx}px` : '';
    tvRightSidebarMediaWrap.style.height = '';
    tvRightSidebarMediaWrap.style.minHeight = `${sidebarLayout.compactMinHeightPx}px`;
    tvRightSidebarMediaWrap.style.maxHeight = `${sidebarLayout.compactMaxHeightPx}px`;

    [tvVideo, tvEmbed, tvImageSlide].forEach((element) => {
        if (!element) {
            return;
        }

        element.style.minHeight = `${sidebarLayout.compactMinHeightPx}px`;
        element.style.maxHeight = `${sidebarLayout.compactMaxHeightPx}px`;
        element.style.height = '100%';
    });

    if (compactSidebarActive) {
        tvProductsPanel.style.padding = '10px';
        tvProductsPanel.style.width = '';
        tvProductsPanel.style.maxWidth = '';
        return;
    }

    tvProductsPanel.style.padding = '';
    tvProductsPanel.style.width = showRightSidebarPanel ? '' : '100%';
    tvProductsPanel.style.maxWidth = showRightSidebarPanel ? '' : '100%';
}

function applyRightSidebarLogoVisibility() {
    if (!tvRightSidebarLogoSlot) {
        return;
    }

    const shouldShowLogo = toBoolean(visualConfig.showRightSidebarPanel, true)
        && toBoolean(visualConfig.showRightSidebarLogo, false);
    const shouldShowSidebarSlot = shouldShowLogo;
    const compactViewport = isCompactViewport();
    const isAndroidRuntime = isAndroidDevice();
    const selectedLogoPosition = isAndroidRuntime
        ? String(visualConfig.rightSidebarLogoPositionAndroid ?? visualConfig.rightSidebarLogoPosition ?? 'sidebar_top')
        : String(visualConfig.rightSidebarLogoPositionWindows ?? visualConfig.rightSidebarLogoPosition ?? 'sidebar_top');
    const useScreenRightVertical = selectedLogoPosition === 'screen_right_vertical';

    const configuredLogoWidth = isAndroidRuntime
        ? (visualConfig.rightSidebarLogoWidthAndroid ?? visualConfig.rightSidebarLogoWidth)
        : (visualConfig.rightSidebarLogoWidthWindows ?? visualConfig.rightSidebarLogoWidth);
    const configuredLogoHeight = isAndroidRuntime
        ? (visualConfig.rightSidebarLogoHeightAndroid ?? visualConfig.rightSidebarLogoHeight)
        : (visualConfig.rightSidebarLogoHeightWindows ?? visualConfig.rightSidebarLogoHeight);

    const rawLogoWidth = Math.max(60, Math.min(1200, Number(configuredLogoWidth || 220)));
    const rawLogoHeight = Math.max(30, Math.min(300, Number(configuredLogoHeight || 58)));
    const logoWidth = compactViewport ? Math.min(rawLogoWidth, 180) : rawLogoWidth;
    const logoHeight = compactViewport ? Math.min(rawLogoHeight, 36) : rawLogoHeight;
    const logoBackgroundColor = String(visualConfig.rightSidebarLogoBackgroundColor || '#0f172a');
    const logoBackgroundTransparent = toBoolean(visualConfig.isRightSidebarLogoBackgroundTransparent, false);
    if (tvRightSidebarLogoSlot) {
        tvRightSidebarLogoSlot.style.position = useScreenRightVertical ? 'fixed' : '';
        tvRightSidebarLogoSlot.style.right = useScreenRightVertical ? '10px' : '';
        tvRightSidebarLogoSlot.style.top = useScreenRightVertical ? '50%' : '';
        tvRightSidebarLogoSlot.style.transform = useScreenRightVertical ? 'translateY(-50%)' : '';
        tvRightSidebarLogoSlot.style.left = useScreenRightVertical ? '' : '';
        tvRightSidebarLogoSlot.style.zIndex = useScreenRightVertical ? '45' : '';
        tvRightSidebarLogoSlot.style.padding = useScreenRightVertical ? '4px' : '';
        tvRightSidebarLogoSlot.style.width = useScreenRightVertical ? `${logoWidth}px` : '';
        tvRightSidebarLogoSlot.style.height = `${logoHeight}px`;
        tvRightSidebarLogoSlot.style.minHeight = `${logoHeight}px`;
        tvRightSidebarLogoSlot.style.background = logoBackgroundTransparent ? 'transparent' : logoBackgroundColor;
        tvRightSidebarLogoSlot.classList.toggle('hidden', !shouldShowSidebarSlot);
        tvRightSidebarLogoSlot.style.display = shouldShowSidebarSlot ? '' : 'none';
    }

    const logoUrl = String(visualConfig.rightSidebarLogoUrl || '').trim();
    if (!tvRightSidebarLogo) {
        return;
    }

    tvRightSidebarLogo.style.maxWidth = `${logoWidth}px`;
    tvRightSidebarLogo.style.maxHeight = `${Math.max(20, logoHeight - 12)}px`;

    if (logoUrl !== '') {
        tvRightSidebarLogo.src = logoUrl;
        tvRightSidebarLogo.classList.remove('hidden');
        tvRightSidebarLogoSlot.classList.remove('is-placeholder');
        return;
    }

    tvRightSidebarLogo.classList.add('hidden');
    tvRightSidebarLogo.removeAttribute('src');
    tvRightSidebarLogoSlot.classList.add('is-placeholder');
}

function applyLeftVerticalLogoVisibility() {
    if (!tvLeftVerticalLogoSlot || !tvLeftVerticalLogo) {
        return;
    }

    const shouldShowLogo = toBoolean(visualConfig.showLeftVerticalLogo, false);
    const logoUrl = String(visualConfig.leftVerticalLogoUrl || '').trim();
    const isAndroidRuntime = isAndroidDevice();
    const configuredLogoWidth = isAndroidRuntime
        ? (visualConfig.leftVerticalLogoWidthAndroid ?? visualConfig.leftVerticalLogoWidth)
        : (visualConfig.leftVerticalLogoWidthWindows ?? visualConfig.leftVerticalLogoWidth);
    const configuredLogoHeight = isAndroidRuntime
        ? (visualConfig.leftVerticalLogoHeightAndroid ?? visualConfig.leftVerticalLogoHeight)
        : (visualConfig.leftVerticalLogoHeightWindows ?? visualConfig.leftVerticalLogoHeight);
    const logoWidth = Math.max(40, Math.min(1000, Number(configuredLogoWidth || 120)));
    const logoHeight = Math.max(40, Math.min(1000, Number(configuredLogoHeight || 220)));
    const logoOffset = logoWidth + 1;
    const compactViewport = isCompactViewport();

    if (tvProductsPanel) {
        if (shouldShowLogo && !compactViewport) {
            // Reserve horizontal space so the left logo does not overlap the product list.
            tvProductsPanel.style.marginLeft = `${logoOffset}px`;
            tvProductsPanel.style.width = `calc(100% - ${logoOffset}px)`;
        } else {
            tvProductsPanel.style.marginLeft = '';
            tvProductsPanel.style.width = '';
        }
    }

    if (compactViewport) {
        tvLeftVerticalLogoSlot.style.left = '8px';
        tvLeftVerticalLogoSlot.style.top = '8px';
        tvLeftVerticalLogoSlot.style.transform = 'none';
    } else {
        tvLeftVerticalLogoSlot.style.left = '';
        tvLeftVerticalLogoSlot.style.top = '';
        tvLeftVerticalLogoSlot.style.transform = '';
    }

    tvLeftVerticalLogoSlot.style.width = `${logoWidth}px`;
    tvLeftVerticalLogoSlot.style.height = `${logoHeight}px`;
    tvLeftVerticalLogoSlot.style.minHeight = `${logoHeight}px`;
    tvLeftVerticalLogoSlot.classList.toggle('hidden', !shouldShowLogo);
    tvLeftVerticalLogoSlot.style.display = shouldShowLogo ? '' : 'none';

    tvLeftVerticalLogo.style.maxWidth = `${logoWidth}px`;
    tvLeftVerticalLogo.style.maxHeight = `${Math.max(20, logoHeight - 10)}px`;

    if (!shouldShowLogo) {
        tvLeftVerticalLogoSlot.classList.add('is-placeholder');
        tvLeftVerticalLogo.classList.add('hidden');
        tvLeftVerticalLogo.removeAttribute('src');
        return;
    }

    if (logoUrl !== '') {
        tvLeftVerticalLogo.src = logoUrl;
        tvLeftVerticalLogo.classList.remove('hidden');
        tvLeftVerticalLogoSlot.classList.remove('is-placeholder');
        return;
    }

    tvLeftVerticalLogoSlot.classList.add('is-placeholder');
    tvLeftVerticalLogo.classList.add('hidden');
    tvLeftVerticalLogo.removeAttribute('src');
}

async function applyAutoFullscreenPreference() {
    if (!isFullscreenSupported()) {
        return;
    }

    if (!getCurrentFullscreenElement()) {
        try {
            await requestDocumentFullscreen();
            clearAutoFullscreenRetryTimer();
            fullscreenWarningShown = false;
        } catch (_error) {
            armAutoFullscreenOnUserGesture();
            scheduleAutoFullscreenRetry(3000);

            if (!fullscreenWarningShown && shouldShowFullscreenBlockedWarning()) {
                fullscreenWarningShown = true;
                updateStatus('Tela cheia bloqueada pelo navegador. Clique/toque uma vez ou rode em modo kiosk.', true);
            }
        }
    }
}

function disarmAutoFullscreenOnUserGesture() {
    if (!autoFullscreenGestureListenerAttached) {
        return;
    }

    window.removeEventListener('pointerdown', handleUserGestureForAutoFullscreen);
    window.removeEventListener('keydown', handleUserGestureForAutoFullscreen);
    window.removeEventListener('touchstart', handleUserGestureForAutoFullscreen);
    autoFullscreenGestureListenerAttached = false;
}

function armAutoFullscreenOnUserGesture() {
    if (!isFullscreenSupported()) {
        return;
    }

    if (autoFullscreenGestureListenerAttached) {
        return;
    }

    window.addEventListener('pointerdown', handleUserGestureForAutoFullscreen, { passive: true, once: true });
    window.addEventListener('keydown', handleUserGestureForAutoFullscreen, { passive: true, once: true });
    window.addEventListener('touchstart', handleUserGestureForAutoFullscreen, { passive: true, once: true });
    autoFullscreenGestureListenerAttached = true;
}

async function handleUserGestureForAutoFullscreen() {
    autoFullscreenGestureListenerAttached = false;

    if (!isFullscreenSupported() || getCurrentFullscreenElement()) {
        return;
    }

    try {
        await requestDocumentFullscreen();
        clearAutoFullscreenRetryTimer();
        fullscreenWarningShown = false;
    } catch (_error) {
        armAutoFullscreenOnUserGesture();
        scheduleAutoFullscreenRetry(2500);
    }
}

function renderProducts(produtos) {
    productsGrid.innerHTML = '';

    const getRowLineSpacing = () => Math.min(40, Math.max(0, Number(visualConfig.rowLineSpacing ?? 12)));

    if (!Array.isArray(produtos) || produtos.length === 0) {
        emptyState.classList.remove('hidden');
        if (productsGroupLabel) {
            productsGroupLabel.textContent = '';
        }
        if (totalProducts) {
            totalProducts.textContent = '0 produtos';
        }
        return;
    }

    const buildGroupLabelFromItems = (items) => {
        const groupNames = Array.from(new Set(
            (Array.isArray(items) ? items : [])
                .map((item) => String(item?.grupo?.nome || '').trim())
                .filter(Boolean)
        ));

        if (groupNames.length === 1) {
            return groupNames[0];
        }

        if (groupNames.length > 1) {
            return groupNames.join(' • ');
        }

        return 'Grupo não informado';
    };

    if (productsGroupLabel) {
        applyGroupLabelStyles(productsGroupLabel);
        productsGroupLabel.textContent = buildGroupLabelFromItems(produtos);
    }

    emptyState.classList.add('hidden');
    if (totalProducts) {
        totalProducts.textContent = `${produtos.length} produtos`;
    }

    const createProductRow = (item) => {
        const row = document.createElement('article');
        row.className = 'rounded-lg bg-slate-950';
        row.style.borderRadius = getRowCornerRadius();

        const rowBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rowBorderWidth ?? 1)));
        const shouldShowRowBorder = Boolean(visualConfig.showBorder) && !visualConfig.isRowBorderTransparent && rowBorderWidth > 0;
        row.style.borderStyle = shouldShowRowBorder ? 'solid' : 'none';
        row.style.borderWidth = shouldShowRowBorder ? `${rowBorderWidth}px` : '0';
        row.style.borderColor = shouldShowRowBorder ? (visualConfig.borderColor || '#334155') : 'transparent';
        row.style.backgroundColor = visualConfig.rowBackgroundColor;
        const compactSidebarActive = isCompactSidebarActive();
        const rowVerticalPadding = Math.min(40, Math.max(0, Number(visualConfig.rowVerticalPadding ?? 9)));
        const effectiveRowVerticalPadding = compactSidebarActive
            ? Math.min(rowVerticalPadding, 8)
            : rowVerticalPadding;
        const rowHorizontalPadding = compactSidebarActive ? 10 : 16;
        row.style.padding = `${effectiveRowVerticalPadding}px ${rowHorizontalPadding}px`;
        if (visualConfig.useGradient) {
            row.style.backgroundImage = `linear-gradient(to bottom, ${visualConfig.gradientStartColor}, ${visualConfig.gradientEndColor})`;
        } else {
            row.style.backgroundImage = 'none';
        }

        const ofertaValue = Number(item.oferta);
        const hasOferta = Number.isFinite(ofertaValue) && ofertaValue >= 1;
        const price = hasOferta ? ofertaValue : item.preco;
        const compactViewport = isCompactViewport();
        const compactScale = getCompactListScale();
        const baseFontSize = Math.min(60, Math.max(10, Number(visualConfig.listFontSize || 16)));
        const effectiveFontSize = compactViewport ? Math.max(10, Math.round(baseFontSize * compactScale)) : baseFontSize;
        const imageUrl = String(item.imagem || '').trim();
        const imageWidth = Math.min(400, Math.max(20, Number(visualConfig.imageWidth || 56)));
        const imageHeight = Math.min(400, Math.max(20, Number(visualConfig.imageHeight || 56)));
        const imageScale = compactSidebarActive ? 0.65 : (compactViewport ? 0.8 : 1);
        const effectiveImageWidth = compactViewport ? Math.max(20, Math.round(imageWidth * imageScale)) : imageWidth;
        const effectiveImageHeight = compactViewport ? Math.max(20, Math.round(imageHeight * imageScale)) : imageHeight;
        const imageMarkup = visualConfig.showImage && imageUrl
            ? `<img src="${imageUrl}" alt="${item.nome ?? 'Produto'}" class="rounded object-cover shrink-0" style="width:${effectiveImageWidth}px;height:${effectiveImageHeight}px" loading="lazy" onerror="this.style.display='none'">`
            : '';

        row.innerHTML = `
            <div class="flex flex-wrap items-center gap-3 min-w-0">
                ${imageMarkup}
                <h3 class="font-semibold flex-1 min-w-0 break-words" style="color:${visualConfig.priceColor};font-size:${effectiveFontSize}px">${item.nome ?? 'Sem nome'}</h3>
                <p class="font-semibold whitespace-nowrap" style="color:${visualConfig.priceColor};font-size:${effectiveFontSize}px">${formatPrice(price)}</p>
            </div>
        `;

        return row;
    };

    const isTwoListMode = isTwoListLayoutEnabled();

    const applyTwoListGridLayout = () => {
        const lineSpacing = getRowLineSpacing();
        productsGrid.className = 'grid gap-3';
        productsGrid.style.display = 'grid';
        productsGrid.style.gridTemplateColumns = 'minmax(0, 1fr) minmax(0, 1fr)';
        productsGrid.style.columnGap = '12px';
        productsGrid.style.rowGap = `${lineSpacing}px`;
        productsGrid.style.alignItems = 'start';
    };

    const applySingleListGridLayout = () => {
        const lineSpacing = getRowLineSpacing();
        productsGrid.className = 'grid grid-cols-1 gap-3';
        productsGrid.style.display = '';
        productsGrid.style.gridTemplateColumns = '';
        productsGrid.style.columnGap = '';
        productsGrid.style.rowGap = `${lineSpacing}px`;
    };

    if (isTwoListMode) {
        applyTwoListGridLayout();

        if (productsGroupLabel) {
            productsGroupLabel.textContent = '';
        }

        const leftColumn = document.createElement('div');
        leftColumn.className = 'space-y-3';
        leftColumn.style.minWidth = '0';
        leftColumn.style.width = '100%';
        leftColumn.style.display = 'grid';
        leftColumn.style.rowGap = `${getRowLineSpacing()}px`;

        const rightColumn = document.createElement('div');
        rightColumn.className = 'space-y-3';
        rightColumn.style.minWidth = '0';
        rightColumn.style.width = '100%';
        rightColumn.style.display = 'grid';
        rightColumn.style.rowGap = `${getRowLineSpacing()}px`;

        const { leftItems, rightItems } = splitTwoListItemsBySide(produtos);

        const leftTitle = document.createElement('p');
        leftTitle.className = 'text-sm font-medium';
        applyGroupLabelStyles(leftTitle);
        leftTitle.textContent = buildGroupLabelFromItems(leftItems);

        const rightTitle = document.createElement('p');
        rightTitle.className = 'text-sm font-medium';
        applyGroupLabelStyles(rightTitle);
        rightTitle.textContent = buildGroupLabelFromItems(rightItems);

        leftColumn.appendChild(leftTitle);
        rightColumn.appendChild(rightTitle);

        leftItems.forEach((item) => leftColumn.appendChild(createProductRow(item)));
        rightItems.forEach((item) => rightColumn.appendChild(createProductRow(item)));

        productsGrid.appendChild(leftColumn);
        productsGrid.appendChild(rightColumn);
        return;
    }

    applySingleListGridLayout();
    produtos.forEach((item) => productsGrid.appendChild(createProductRow(item)));
}

function clearPaginationTimer() {
    if (paginationTimer) {
        clearInterval(paginationTimer);
        paginationTimer = null;
    }
}

function shouldKeepRightSidebarVideoRunningDuringDedicatedFullScreen() {
    const mediaMode = String(getRightSidebarMediaType() || '').toLowerCase();
    const isProductCarouselEnabled = toBoolean(visualConfig.rightSidebarProductCarouselEnabled, false);
    const showRightSidebarPanel = toBoolean(visualConfig.showRightSidebarPanel, true);
    return mediaMode === 'video' && !isProductCarouselEnabled && showRightSidebarPanel;
}

function ensureRightSidebarMediaRestoredAfterDedicatedFullScreen() {
    if (!shouldKeepRightSidebarVideoRunningDuringDedicatedFullScreen()) {
        return false;
    }

    const configuredVideos = parseConfiguredVideoUrls();
    if (configuredVideos.length > 0) {
        startVideoPlaylist(configuredVideos);
        return true;
    }

    if (Array.isArray(videoPlaylistItems) && videoPlaylistItems.length > 0) {
        const safeIndex = Math.max(0, Math.min(videoPlaylistItems.length - 1, Number(currentVideoIndex || 0)));
        applyVideoSource(videoPlaylistItems[safeIndex]);
        return true;
    }

    return false;
}

function shouldPauseExternalTimersForDedicatedFullScreen() {
    const mediaMode = String(getRightSidebarMediaType() || '').toLowerCase();
    const isProductCarouselEnabled = toBoolean(visualConfig.rightSidebarProductCarouselEnabled, false);
    return !(mediaMode === 'video' && !isProductCarouselEnabled);
}

function pauseExternalTimersForDedicatedFullScreen() {
    if (!shouldPauseExternalTimersForDedicatedFullScreen()) {
        dedicatedFullScreenExternalTimersPaused = false;
        return;
    }

    if (dedicatedFullScreenExternalTimersPaused) {
        return;
    }

    dedicatedFullScreenExternalTimersPaused = true;
    clearPaginationTimer();
    clearSidebarProductTimer();
    clearImageSlideTimer();
}

function resumeExternalTimersForDedicatedFullScreen() {
    if (!shouldPauseExternalTimersForDedicatedFullScreen()) {
        dedicatedFullScreenExternalTimersPaused = false;
        return;
    }

    if (!dedicatedFullScreenExternalTimersPaused) {
        return;
    }

    dedicatedFullScreenExternalTimersPaused = false;

    const fallbackProducts = readCachedProducts();
    const productsToResume = latestProductsForPagination.length > 0
        ? latestProductsForPagination
        : (Array.isArray(fallbackProducts) ? fallbackProducts : []);

    if (productsToResume.length > 0) {
        renderProductsWithPagination(productsToResume);
    }
}

function splitTwoListItemsBySide(items) {
    const list = Array.isArray(items) ? items : [];
    const leftGroupSet = new Set((visualConfig.productListLeftGroupIds || []).map((id) => Number(id)).filter((id) => id > 0));
    const rightGroupSet = new Set((visualConfig.productListRightGroupIds || []).map((id) => Number(id)).filter((id) => id > 0));

    let leftItems = [];
    let rightItems = [];

    if (leftGroupSet.size > 0 || rightGroupSet.size > 0) {
        list.forEach((item) => {
            const groupId = Number(item?.grupo?.id || 0);

            if (leftGroupSet.has(groupId)) {
                leftItems.push(item);
            }

            if (rightGroupSet.has(groupId)) {
                rightItems.push(item);
            }
        });
    } else {
        const middle = Math.ceil(list.length / 2);
        leftItems = list.slice(0, middle);
        rightItems = list.slice(middle);
    }

    return { leftItems, rightItems };
}
function isTwoListLayoutEnabled() {
    return String(visualConfig.productListType || '1') === '2';
}

function renderProductsWithPagination(produtos) {
    clearPaginationTimer();

    const list = Array.isArray(produtos) ? produtos : [];
    latestProductsForPagination = list.slice();
    const rowLineSpacing = Math.min(40, Math.max(0, Number(visualConfig.rowLineSpacing ?? 12)));
    const isTwoListMode = isTwoListLayoutEnabled();

    if (isTwoListMode) {
        if (list.length === 0) {
            renderProducts(list);
            return;
        }

        const { leftItems, rightItems } = splitTwoListItemsBySide(list);

        const buildGroupLabelFromItems = (items) => {
            const groupNames = Array.from(new Set(
                (Array.isArray(items) ? items : [])
                    .map((item) => String(item?.grupo?.nome || '').trim())
                    .filter(Boolean)
            ));

            if (groupNames.length === 1) {
                return groupNames[0];
            }

            if (groupNames.length > 1) {
                return groupNames.join(' • ');
            }

            return 'Grupo não informado';
        };

        const createProductRow = (item) => {
            const row = document.createElement('article');
            row.className = 'rounded-lg bg-slate-950';
            row.style.borderRadius = getRowCornerRadius();

            const rowBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rowBorderWidth ?? 1)));
            const shouldShowRowBorder = Boolean(visualConfig.showBorder) && !visualConfig.isRowBorderTransparent && rowBorderWidth > 0;
            row.style.borderStyle = shouldShowRowBorder ? 'solid' : 'none';
            row.style.borderWidth = shouldShowRowBorder ? `${rowBorderWidth}px` : '0';
            row.style.borderColor = shouldShowRowBorder ? (visualConfig.borderColor || '#334155') : 'transparent';
            row.style.backgroundColor = visualConfig.rowBackgroundColor;
            const compactSidebarActive = isCompactSidebarActive();
            const rowVerticalPadding = Math.min(40, Math.max(0, Number(visualConfig.rowVerticalPadding ?? 9)));
            const effectiveRowVerticalPadding = compactSidebarActive
                ? Math.min(rowVerticalPadding, 8)
                : rowVerticalPadding;
            const rowHorizontalPadding = compactSidebarActive ? 10 : 16;
            row.style.padding = `${effectiveRowVerticalPadding}px ${rowHorizontalPadding}px`;
            if (visualConfig.useGradient) {
                row.style.backgroundImage = `linear-gradient(to bottom, ${visualConfig.gradientStartColor}, ${visualConfig.gradientEndColor})`;
            } else {
                row.style.backgroundImage = 'none';
            }

            const ofertaValue = Number(item.oferta);
            const hasOferta = Number.isFinite(ofertaValue) && ofertaValue >= 1;
            const price = hasOferta ? ofertaValue : item.preco;
            const compactViewport = isCompactViewport();
            const compactScale = getCompactListScale();
            const baseFontSize = Math.min(60, Math.max(10, Number(visualConfig.listFontSize || 16)));
            const effectiveFontSize = compactViewport ? Math.max(10, Math.round(baseFontSize * compactScale)) : baseFontSize;
            const imageUrl = String(item.imagem || '').trim();
            const imageWidth = Math.min(400, Math.max(20, Number(visualConfig.imageWidth || 56)));
            const imageHeight = Math.min(400, Math.max(20, Number(visualConfig.imageHeight || 56)));
            const imageScale = compactSidebarActive ? 0.65 : (compactViewport ? 0.8 : 1);
            const effectiveImageWidth = compactViewport ? Math.max(20, Math.round(imageWidth * imageScale)) : imageWidth;
            const effectiveImageHeight = compactViewport ? Math.max(20, Math.round(imageHeight * imageScale)) : imageHeight;
            const imageMarkup = visualConfig.showImage && imageUrl
                ? `<img src="${imageUrl}" alt="${item.nome ?? 'Produto'}" class="rounded object-cover shrink-0" style="width:${effectiveImageWidth}px;height:${effectiveImageHeight}px" loading="lazy" onerror="this.style.display='none'">`
                : '';

            row.innerHTML = `
                <div class="flex flex-wrap items-center gap-3 min-w-0">
                    ${imageMarkup}
                    <h3 class="font-semibold flex-1 min-w-0 break-words" style="color:${visualConfig.priceColor};font-size:${effectiveFontSize}px">${item.nome ?? 'Sem nome'}</h3>
                    <p class="font-semibold whitespace-nowrap" style="color:${visualConfig.priceColor};font-size:${effectiveFontSize}px">${formatPrice(price)}</p>
                </div>
            `;

            return row;
        };

        const renderTwoListColumns = (pageLeftItems, pageRightItems) => {
            productsGrid.innerHTML = '';
            productsGrid.className = 'grid gap-3';
            productsGrid.style.display = 'grid';
            productsGrid.style.gridTemplateColumns = 'minmax(0, 1fr) minmax(0, 1fr)';
            productsGrid.style.columnGap = '12px';
            productsGrid.style.rowGap = `${rowLineSpacing}px`;
            productsGrid.style.alignItems = 'start';

            if (productsGroupLabel) {
                productsGroupLabel.textContent = '';
            }

            const leftColumn = document.createElement('div');
            leftColumn.className = 'space-y-3';
            leftColumn.style.minWidth = '0';
            leftColumn.style.width = '100%';
            leftColumn.style.display = 'grid';
            leftColumn.style.rowGap = `${rowLineSpacing}px`;
            const leftTitle = document.createElement('p');
            leftTitle.className = 'text-sm font-medium';
            applyGroupLabelStyles(leftTitle);
            leftTitle.textContent = buildGroupLabelFromItems(pageLeftItems);
            leftColumn.appendChild(leftTitle);
            pageLeftItems.forEach((item) => leftColumn.appendChild(createProductRow(item)));

            const rightColumn = document.createElement('div');
            rightColumn.className = 'space-y-3';
            rightColumn.style.minWidth = '0';
            rightColumn.style.width = '100%';
            rightColumn.style.display = 'grid';
            rightColumn.style.rowGap = `${rowLineSpacing}px`;
            const rightTitle = document.createElement('p');
            rightTitle.className = 'text-sm font-medium';
            applyGroupLabelStyles(rightTitle);
            rightTitle.textContent = buildGroupLabelFromItems(pageRightItems);
            rightColumn.appendChild(rightTitle);
            pageRightItems.forEach((item) => rightColumn.appendChild(createProductRow(item)));

            productsGrid.appendChild(leftColumn);
            productsGrid.appendChild(rightColumn);
            emptyState.classList.add('hidden');

            if (totalProducts) {
                totalProducts.textContent = `${pageLeftItems.length + pageRightItems.length} produtos`;
            }
        };

        if (!visualConfig.isPaginationEnabled) {
            renderTwoListColumns(leftItems, rightItems);
            return;
        }

        const pageSize = Math.max(1, Number(visualConfig.pageSize) || 10);
        const intervalSeconds = Math.max(1, Number(visualConfig.paginationInterval) || 5);
        const totalPagesLeft = Math.max(1, Math.ceil(leftItems.length / pageSize));
        const totalPagesRight = Math.max(1, Math.ceil(rightItems.length / pageSize));

        if (totalPagesLeft <= 1 && totalPagesRight <= 1) {
            renderTwoListColumns(leftItems, rightItems);
            return;
        }

        let currentPageLeft = 0;
        let currentPageRight = 0;
        const renderPage = () => {
            const startLeft = totalPagesLeft > 1 ? currentPageLeft * pageSize : 0;
            const endLeft = startLeft + pageSize;
            const startRight = totalPagesRight > 1 ? currentPageRight * pageSize : 0;
            const endRight = startRight + pageSize;
            const pageLeftItems = leftItems.slice(startLeft, endLeft);
            const pageRightItems = rightItems.slice(startRight, endRight);
            renderTwoListColumns(pageLeftItems, pageRightItems);

            if (totalPagesLeft > 1) {
                currentPageLeft = (currentPageLeft + 1) % totalPagesLeft;
            }

            if (totalPagesRight > 1) {
                currentPageRight = (currentPageRight + 1) % totalPagesRight;
            }
        };

        renderPage();
        paginationTimer = setInterval(renderPage, intervalSeconds * 1000);
        return;
    }

    productsGrid.style.display = '';
    productsGrid.style.gridTemplateColumns = '';
    productsGrid.style.columnGap = '';
    productsGrid.style.rowGap = `${rowLineSpacing}px`;
    productsGrid.style.alignItems = '';

    if (!visualConfig.isPaginationEnabled || list.length === 0) {
        renderProducts(list);
        return;
    }

    const pageSize = Math.max(1, Number(visualConfig.pageSize) || 10);
    const intervalSeconds = Math.max(1, Number(visualConfig.paginationInterval) || 5);
    const totalPages = Math.ceil(list.length / pageSize);

    if (totalPages <= 1) {
        renderProducts(list);
        return;
    }

    let currentPage = 0;

    const renderPage = () => {
        const start = currentPage * pageSize;
        const end = start + pageSize;
        renderProducts(list.slice(start, end));
        currentPage = (currentPage + 1) % totalPages;
    };

    renderPage();
    paginationTimer = setInterval(renderPage, intervalSeconds * 1000);
}

async function loadVisualConfig(token) {
    if (!token) {
        return false;
    }

    try {
        const response = await fetch(configEndpoint, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (_error) {
            payload = null;
        }

        if (response.status === 401 && shouldForceReconfigureFromPayload(payload)) {
            redirectToConfigKeepingTokenHistory('Token da TV desativado ou removido no admin. Configure um novo token.');
            return false;
        }

        if (response.status === 401) {
            reportNonCriticalAuthWarning('loadVisualConfig', payload);
            return false;
        }

        if (!response.ok || !payload?.success) {
            setOfflineIndicatorVisible(!navigator.onLine);
            return applyCachedVisualConfigIfAny();
        }

        Object.assign(visualConfig, payload.data || {});
        visualConfig.apiRefreshInterval = Math.max(5, Math.min(3600, Number(visualConfig.apiRefreshInterval || refreshSeconds || 30)));
        visualConfig.showImage = Boolean(visualConfig.showImage);
        visualConfig.showVideoPanel = toBoolean(visualConfig.showVideoPanel, true);
        visualConfig.showRightSidebarPanel = toBoolean(visualConfig.showRightSidebarPanel, true);
        visualConfig.showRightSidebarLogo = toBoolean(visualConfig.showRightSidebarLogo, false);
        visualConfig.showLeftVerticalLogo = toBoolean(visualConfig.showLeftVerticalLogo, false);
        visualConfig.rightSidebarLogoPosition = String(visualConfig.rightSidebarLogoPosition || 'sidebar_top');
        visualConfig.rightSidebarLogoPositionWindows = String(visualConfig.rightSidebarLogoPositionWindows || visualConfig.rightSidebarLogoPosition || 'sidebar_top');
        visualConfig.rightSidebarLogoPositionAndroid = String(visualConfig.rightSidebarLogoPositionAndroid || visualConfig.rightSidebarLogoPosition || 'sidebar_top');
        visualConfig.rightSidebarLogoUrl = String(visualConfig.rightSidebarLogoUrl || '').trim();
        visualConfig.rightSidebarLogoWidth = Math.max(60, Math.min(1200, Number(visualConfig.rightSidebarLogoWidth || 220)));
        visualConfig.rightSidebarLogoHeight = Math.max(30, Math.min(300, Number(visualConfig.rightSidebarLogoHeight || 58)));
        visualConfig.rightSidebarLogoWidthWindows = Math.max(60, Math.min(1200, Number(visualConfig.rightSidebarLogoWidthWindows ?? visualConfig.rightSidebarLogoWidth ?? 220)));
        visualConfig.rightSidebarLogoHeightWindows = Math.max(30, Math.min(300, Number(visualConfig.rightSidebarLogoHeightWindows ?? visualConfig.rightSidebarLogoHeight ?? 58)));
        visualConfig.rightSidebarLogoWidthAndroid = Math.max(60, Math.min(1200, Number(visualConfig.rightSidebarLogoWidthAndroid ?? visualConfig.rightSidebarLogoWidth ?? 220)));
        visualConfig.rightSidebarLogoHeightAndroid = Math.max(30, Math.min(300, Number(visualConfig.rightSidebarLogoHeightAndroid ?? visualConfig.rightSidebarLogoHeight ?? 58)));
        visualConfig.leftVerticalLogoUrl = String(visualConfig.leftVerticalLogoUrl || '').trim();
        visualConfig.leftVerticalLogoWidth = Math.max(40, Math.min(1000, Number(visualConfig.leftVerticalLogoWidth || 120)));
        visualConfig.leftVerticalLogoHeight = Math.max(40, Math.min(1000, Number(visualConfig.leftVerticalLogoHeight || 220)));
        visualConfig.leftVerticalLogoWidthWindows = Math.max(40, Math.min(1000, Number(visualConfig.leftVerticalLogoWidthWindows ?? visualConfig.leftVerticalLogoWidth ?? 120)));
        visualConfig.leftVerticalLogoHeightWindows = Math.max(40, Math.min(1000, Number(visualConfig.leftVerticalLogoHeightWindows ?? visualConfig.leftVerticalLogoHeight ?? 220)));
        visualConfig.leftVerticalLogoWidthAndroid = Math.max(40, Math.min(1000, Number(visualConfig.leftVerticalLogoWidthAndroid ?? visualConfig.leftVerticalLogoWidth ?? 120)));
        visualConfig.leftVerticalLogoHeightAndroid = Math.max(40, Math.min(1000, Number(visualConfig.leftVerticalLogoHeightAndroid ?? visualConfig.leftVerticalLogoHeight ?? 220)));
        visualConfig.rightSidebarLogoBackgroundColor = String(visualConfig.rightSidebarLogoBackgroundColor || '#0f172a');
        visualConfig.isRightSidebarLogoBackgroundTransparent = toBoolean(visualConfig.isRightSidebarLogoBackgroundTransparent, false);
        visualConfig.showTitle = toBoolean(visualConfig.showTitle, true);
        visualConfig.titleText = String(visualConfig.titleText || 'Lista de Produtos (TV)');
        visualConfig.isTitleDynamic = toBoolean(visualConfig.isTitleDynamic, false);
        visualConfig.titlePosition = String(visualConfig.titlePosition || 'top').toLowerCase() === 'footer' ? 'footer' : 'top';
        visualConfig.titleFontSize = Math.min(96, Math.max(10, Number(visualConfig.titleFontSize || 32)));
        visualConfig.titleFontFamily = String(visualConfig.titleFontFamily || 'arial').toLowerCase();
        visualConfig.titleTextColor = String(visualConfig.titleTextColor || '#f8fafc');
        visualConfig.titleBackgroundColor = String(visualConfig.titleBackgroundColor || '#0f172a');
        visualConfig.isTitleBackgroundTransparent = toBoolean(visualConfig.isTitleBackgroundTransparent, false);
        visualConfig.showTitleBorder = toBoolean(visualConfig.showTitleBorder, true);
        visualConfig.isMainBorderEnabled = toBoolean(visualConfig.isMainBorderEnabled, false);
        visualConfig.isRoundedCornersEnabled = toBoolean(visualConfig.isRoundedCornersEnabled, true);
        visualConfig.isRowRoundedEnabled = toBoolean(visualConfig.isRowRoundedEnabled, false);
        visualConfig.mainBorderColor = String(visualConfig.mainBorderColor || '#000000');
        visualConfig.mainBorderWidth = Math.min(40, Math.max(0, Number(visualConfig.mainBorderWidth ?? 1)));
        visualConfig.productListType = String(visualConfig.productListType || '1') === '2' ? '2' : '1';
        visualConfig.productListLeftGroupIds = Array.isArray(visualConfig.productListLeftGroupIds)
            ? visualConfig.productListLeftGroupIds.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0)
            : [];
        visualConfig.productListRightGroupIds = Array.isArray(visualConfig.productListRightGroupIds)
            ? visualConfig.productListRightGroupIds.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0)
            : [];
        visualConfig.rowBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rowBorderWidth ?? 1)));
        visualConfig.listBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.listBorderWidth ?? 1)));
        visualConfig.rightSidebarBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rightSidebarBorderWidth ?? 1)));
        visualConfig.rightSidebarMediaType = getRightSidebarMediaType();
        visualConfig.rightSidebarImageInterval = Math.max(1, Number(visualConfig.rightSidebarImageInterval || 8));
        visualConfig.fullScreenSlideImageUrls = String(visualConfig.fullScreenSlideImageUrls || '').trim();
        visualConfig.fullScreenSlideInterval = Math.max(1, Number(visualConfig.fullScreenSlideInterval || 8));
        visualConfig.fullScreenSlideReturnDelaySeconds = Math.max(0, Math.min(86400, Number(visualConfig.fullScreenSlideReturnDelaySeconds || 0)));
        visualConfig.fullScreenSlideEnabled = toBoolean(visualConfig.fullScreenSlideEnabled, false);
        visualConfig.fullScreenSlideStartDate = String(visualConfig.fullScreenSlideStartDate || '').trim();
        visualConfig.fullScreenSlideEndDate = String(visualConfig.fullScreenSlideEndDate || '').trim();
        visualConfig.fullScreenSlideEnabledWindows = toBoolean(visualConfig.fullScreenSlideEnabledWindows, true);
        visualConfig.fullScreenSlideEnabledAndroid = toBoolean(visualConfig.fullScreenSlideEnabledAndroid, true);
        visualConfig.fullScreenSlideImageWidthWindows = Math.max(0, Math.min(3840, Number(visualConfig.fullScreenSlideImageWidthWindows || 0)));
        visualConfig.fullScreenSlideImageHeightWindows = Math.max(0, Math.min(2160, Number(visualConfig.fullScreenSlideImageHeightWindows || 0)));
        visualConfig.fullScreenSlideImageWidthAndroid = Math.max(0, Math.min(3840, Number(visualConfig.fullScreenSlideImageWidthAndroid || 0)));
        visualConfig.fullScreenSlideImageHeightAndroid = Math.max(0, Math.min(2160, Number(visualConfig.fullScreenSlideImageHeightAndroid || 0)));

        const nextFullScreenSignature = buildDedicatedFullScreenSlideSignature();

        fullScreenSlideConfigSignature = nextFullScreenSignature;
        syncDedicatedFullScreenCompletionState();

        visualConfig.rightSidebarImageSchedules = Array.isArray(visualConfig.rightSidebarImageSchedules)
            ? visualConfig.rightSidebarImageSchedules
            : [];
        visualConfig.rightSidebarAndroidHeight = Math.max(0, Math.min(1500, Number(visualConfig.rightSidebarAndroidHeight || 0)));
        visualConfig.rightSidebarAndroidWidth = Math.max(0, Math.min(1000, Number(visualConfig.rightSidebarAndroidWidth || 0)));
        visualConfig.rightSidebarAndroidVerticalOffset = Math.max(-300, Math.min(300, Number(visualConfig.rightSidebarAndroidVerticalOffset || 0)));
        visualConfig.rowVerticalPadding = Math.min(40, Math.max(0, Number(visualConfig.rowVerticalPadding ?? 9)));
        visualConfig.rowLineSpacing = Math.min(40, Math.max(0, Number(visualConfig.rowLineSpacing ?? 12)));
        visualConfig.productListVerticalOffset = Math.max(-300, Math.min(300, Number(visualConfig.productListVerticalOffset ?? 0)));
        visualConfig.groupLabelVerticalOffset = Math.max(-300, Math.min(300, Number(visualConfig.groupLabelVerticalOffset ?? 0)));
        visualConfig.groupLabelFontFamily = String(visualConfig.groupLabelFontFamily || 'arial').toLowerCase();
        visualConfig.showGroupLabelBadge = toBoolean(visualConfig.showGroupLabelBadge, false);
        visualConfig.groupLabelBadgeColor = String(visualConfig.groupLabelBadgeColor || '#0f172a');
        const normalizedRightSidebarFit = String(visualConfig.rightSidebarImageFit || 'scale-down').toLowerCase();
        visualConfig.rightSidebarImageFit = normalizedRightSidebarFit === 'cover'
            ? 'cover'
            : (normalizedRightSidebarFit === 'contain' ? 'contain' : 'scale-down');
        visualConfig.rightSidebarHybridVideoDuration = Math.max(1, Number(visualConfig.rightSidebarHybridVideoDuration || 2));
        visualConfig.rightSidebarHybridImageDuration = Math.max(1, Number(visualConfig.rightSidebarHybridImageDuration || 4));
        document.body.style.backgroundColor = visualConfig.appBackgroundColor;
        const imageUrl = String(visualConfig.backgroundImageUrl || '').trim();
        if (visualConfig.showBackgroundImage && imageUrl) {
            document.body.style.backgroundImage = `url("${imageUrl}")`;
            document.body.style.backgroundRepeat = 'no-repeat';
            document.body.style.backgroundPosition = 'center center';
            document.body.style.backgroundSize = 'cover';
        } else {
            document.body.style.backgroundImage = 'none';
            document.body.style.backgroundRepeat = '';
            document.body.style.backgroundPosition = '';
            document.body.style.backgroundSize = '';
        }

        applyProductsPanelBackground();
        applyProductListVerticalOffset();
        applyVideoBackground();
        applyRightSidebarBorder();
        applyGeneralBorder();
        applyRightSidebarPanelVisibility();
        applyCompactRightSidebarLayout();
        applyLeftVerticalLogoVisibility();
        applyTitleVisibility();
        await applyAutoFullscreenPreference();
        applyProductsRefreshInterval(visualConfig.apiRefreshInterval);
        saveCachedVisualConfig(visualConfig);
        setOfflineIndicatorVisible(false);
        return true;
    } catch (_error) {
        setOfflineIndicatorVisible(true);
        return applyCachedVisualConfigIfAny();
    }
}

async function loadProducts() {
    const token = getReliableDeviceToken();

    if (!token) {
        clearPaginationTimer();
        redirectToConfigPage();
        return;
    }

    updateStatus('Carregando produtos...');

    const productsEndpoint = resolveSafeProductsApiEndpoint();

    const visualConfigLoaded = await loadVisualConfig(token);
    if (!visualConfigLoaded) {
        const reusedProducts = renderCachedProductsIfAny();
        setOfflineIndicatorVisible(true);
        if (!reusedProducts) {
            updateStatus('Falha temporaria ao carregar configuracao da TV. Mantendo tela atual.', true);
        }
        return;
    }

    try {
        let response = await fetch(productsEndpoint, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        if (!response.ok && productsEndpoint !== '/api/tv/produtos') {
            // If a custom endpoint fails, retry once using local default endpoint.
            response = await fetch('/api/tv/produtos', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                },
            });
        }

        let payload = null;
        try {
            payload = await response.json();
        } catch (_error) {
            payload = null;
        }

        if (response.status === 401 && shouldForceReconfigureFromPayload(payload)) {
            redirectToConfigKeepingTokenHistory('Token da TV desativado ou removido no admin. Configure um novo token.');
            return;
        }

        if (response.status === 401) {
            reportNonCriticalAuthWarning('loadProducts', payload);
            updateWarningStatus('Aviso: autenticacao temporaria nos produtos. Mantendo ultimo conteudo.');
            return;
        }

        if (!response.ok || !payload?.success) {
            throw new Error('Falha ao consultar API de produtos da TV.');
        }

        const produtos = payload?.data?.produtos ?? [];
        renderProductsWithPagination(produtos);
        await applyRightSidebarMediaMode(token, produtos);
        saveCachedProducts(produtos);
        setOfflineIndicatorVisible(false);
        try {
            window.__tvProdutosBooted = true;
        } catch (_error) {
        }
        updateStatus(`Atualizado em ${new Date().toLocaleTimeString('pt-BR')}.`);
    } catch (error) {
        const reusedProducts = renderCachedProductsIfAny();
        setOfflineIndicatorVisible(true);
        if (reusedProducts) {
            const cached = readCachedProducts() || [];
            await applyRightSidebarMediaMode(token, cached);
            return;
        }

        updateStatus(error.message || 'Erro ao carregar produtos.', true);
    }
}

if (loadButton) {
    loadButton.addEventListener('click', loadProducts);
}

if (tokenInput) {
    tokenInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            loadProducts();
        }
    });
}

window.addEventListener('pointerdown', markAudioAutoplayUnlocked, { passive: true });
window.addEventListener('keydown', markAudioAutoplayUnlocked, { passive: true });
window.addEventListener('touchstart', markAudioAutoplayUnlocked, { passive: true });

window.addEventListener('focus', () => {
    scheduleAutoFullscreenRetry(1200);
});

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        scheduleAutoFullscreenRetry(1200);
        applyLeftVerticalLogoVisibility();
    }
});

window.addEventListener('resize', () => {
    scheduleCompactResponsiveLayoutRefresh();
});

window.addEventListener('orientationchange', () => {
    scheduleCompactResponsiveLayoutRefresh();
});

if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', () => {
        scheduleCompactResponsiveLayoutRefresh();
    });
    window.visualViewport.addEventListener('scroll', () => {
        scheduleCompactResponsiveLayoutRefresh();
    });
}

window.addEventListener('offline', () => {
    setOfflineIndicatorVisible(true);
    renderCachedProductsIfAny();
});

window.addEventListener('online', () => {
    setOfflineIndicatorVisible(false);
    loadProducts();
});

setOfflineIndicatorVisible(!navigator.onLine);

if (fullscreenTestButton) {
    fullscreenTestButton.addEventListener('click', async () => {
        await toggleFullscreenByUserAction();
    });

    document.addEventListener('fullscreenchange', setFullscreenTestButtonState);
    document.addEventListener('webkitfullscreenchange', setFullscreenTestButtonState);
    document.addEventListener('msfullscreenchange', setFullscreenTestButtonState);
    setFullscreenTestButtonState();
}

window.addEventListener('keydown', async (event) => {
    const key = String(event.key || '').toLowerCase();
    const keyCode = Number(event.keyCode || event.which || 0);
    const isEnterKey = key === 'enter' || key === 'numpadenter' || keyCode === 13;

    if (!isEnterKey) {
        return;
    }

    if (isTypingElement(event.target)) {
        return;
    }

    if (getCurrentFullscreenElement() && document.activeElement !== fullscreenTestButton) {
        if (typeof window.showBolinhaForExit === 'function') {
            window.showBolinhaForExit(true);
        }
        return;
    }

    event.preventDefault();
    await toggleFullscreenByUserAction();
});

enforceValidTokenOrRedirect().then((isTokenValid) => {
    if (!isTokenValid) {
        return;
    }

    loadProducts();
});

applyProductsRefreshInterval(refreshSeconds);

if (tvVideo) {
    tvVideo.addEventListener('loadedmetadata', () => ensureVideoAudioEnabled(tvVideo.muted));
    tvVideo.addEventListener('play', () => ensureVideoAudioEnabled(tvVideo.muted));
    tvVideo.addEventListener('ended', () => {
        if (sidebarMixedVideoTurnActive && typeof sidebarMixedOnVideoFinished === 'function') {
            sidebarMixedOnVideoFinished();
            return;
        }

        const token = getReliableDeviceToken();

        if (token) {
            handleHybridVideoItemCompleted(token);

            if (getRightSidebarMediaType() === 'hybrid' && rightSidebarHybridPhase === 'image') {
                return;
            }
        }

        if (videoPlaylistItems.length > 1) {
            playNextVideoInPlaylist();
            return;
        }

        ensureVideoAudioEnabled(tvVideo.muted);
        tvVideo.currentTime = 0;
        tvVideo.play().catch(() => {});
    });

    tvVideo.addEventListener('error', () => {
        if (videoHint) {
            videoHint.textContent = 'Sem vídeo padrão. Coloque um arquivo em public/tv/videos/demo.mp4.';
        }
    });
}

window.addEventListener('beforeunload', () => {
    try {
        sessionStorage.setItem(DEDICATED_FULLSCREEN_SKIP_ON_NEXT_LOAD_KEY, '1');
    } catch (_error) {
    }

    try {
        const current = String(window.name || '');
        if (!current.includes(WINDOW_NAME_SKIP_MARKER)) {
            window.name = current ? `${current};${WINDOW_NAME_SKIP_MARKER}` : WINDOW_NAME_SKIP_MARKER;
        }
    } catch (_error) {
    }
});

if (tvImageSlide) {
    tvImageSlide.addEventListener('error', () => {
        handleCurrentSlideImageLoadError();
    });
}

window.addEventListener('message', (event) => {
    if (!event.origin.includes('youtube.com')) {
        return;
    }

    let payload;

    if (typeof event.data === 'string') {
        try {
            payload = JSON.parse(event.data);
        } catch (_error) {
            return;
        }
    } else {
        payload = event.data;
    }

    if (!payload) {
        return;
    }

    let finished = false;

    if (payload.event === 'onStateChange' && payload.info === 0) {
        finished = true;
    }

    if (payload.event === 'infoDelivery' && payload.info && payload.info.playerState === 0) {
        finished = true;
    }

    if (payload.info === 0 || payload.data === 0) {
        finished = true;
    }

    if (finished && sidebarMixedVideoTurnActive && typeof sidebarMixedOnVideoFinished === 'function') {
        sidebarMixedOnVideoFinished();
        return;
    }

    if (finished && videoPlaylistItems.length > 1) {
        clearVideoFallbackTimer();
        playNextVideoInPlaylist();
        return;
    }

    if (finished) {
        const token = getReliableDeviceToken();
        if (token) {
            handleHybridVideoItemCompleted(token);
        }
    }
});
