export function createRightSidebarMediaModeModule(deps) {
    const state = deps.state;
    const api = {};

    api.getRightSidebarMediaType = function getRightSidebarMediaType() {
        const mode = String(deps.visualConfig.rightSidebarMediaType || 'video').trim().toLowerCase();

        // If videos are disabled, force image mode for the right sidebar media logic.
        if (!deps.toBoolean(deps.visualConfig.showVideoPanel, true)) {
            return 'image';
        }

        if (mode === 'image' || mode === 'hybrid') {
            return mode;
        }

        return 'video';
    };

    api.getHybridVideoCountLimit = function getHybridVideoCountLimit() {
        return Math.max(1, Number(deps.visualConfig.rightSidebarHybridVideoDuration || 2));
    };

    api.getHybridImageCountLimit = function getHybridImageCountLimit() {
        return Math.max(1, Number(deps.visualConfig.rightSidebarHybridImageDuration || 4));
    };

    api.getNextHybridPhase = function getNextHybridPhase(phase) {
        return phase === 'image' ? 'video' : 'image';
    };

    api.applyRightSidebarModeOnce = async function applyRightSidebarModeOnce(token, mode) {
        if (mode === 'image') {
            deps.startImageSlideMode();
            return;
        }

        deps.stopImageSlideMode();
        await deps.loadVideoPlaylist(token);
    };

    api.switchRightSidebarHybridPhase = async function switchRightSidebarHybridPhase(token, nextPhase) {
        if (api.getRightSidebarMediaType() !== 'hybrid') {
            return;
        }

        if (state.rightSidebarHybridSwitching) {
            return;
        }

        state.rightSidebarHybridSwitching = true;
        try {
            state.rightSidebarHybridPhase = nextPhase === 'image' ? 'image' : 'video';

            if (state.rightSidebarHybridPhase === 'video') {
                state.rightSidebarHybridVideoCountInPhase = 0;
                state.forceVideoPlaylistApplyOnce = true;
                await api.applyRightSidebarModeOnce(token, 'video');
            } else {
                state.rightSidebarHybridImageCountInPhase = 0;
                deps.startHybridImagePhase(token);
            }
        } finally {
            state.rightSidebarHybridSwitching = false;
        }
    };

    api.handleHybridVideoItemCompleted = function handleHybridVideoItemCompleted(token) {
        if (api.getRightSidebarMediaType() !== 'hybrid' || state.rightSidebarHybridPhase !== 'video') {
            return;
        }

        const now = Date.now();
        if (now - state.rightSidebarHybridLastCompletedAt < 700) {
            return;
        }

        state.rightSidebarHybridLastCompletedAt = now;
        state.rightSidebarHybridVideoCountInPhase += 1;

        if (state.rightSidebarHybridVideoCountInPhase >= api.getHybridVideoCountLimit()) {
            state.rightSidebarHybridVideoCountInPhase = 0;
            state.rightSidebarHybridImageCountInPhase = 0;

            if (Array.isArray(state.videoPlaylistItems) && state.videoPlaylistItems.length > 0) {
                state.currentVideoIndex = (state.currentVideoIndex + 1) % state.videoPlaylistItems.length;
            }

            api.switchRightSidebarHybridPhase(token, 'image');
        }
    };

    api.applyRightSidebarMediaMode = async function applyRightSidebarMediaMode(token, productsForSidebar = null, options = {}) {
        const currentDedicatedSignature = deps.buildDedicatedFullScreenSlideSignature();
        if (currentDedicatedSignature) {
            state.fullScreenSlideConfigSignature = currentDedicatedSignature;
        }

        deps.syncDedicatedFullScreenCompletionState();

        const hasDedicatedFullScreenSlide = deps.parseConfiguredDedicatedFullScreenSlideUrls().length > 0;
        const shouldSkipDedicatedForCurrentLoad = deps.shouldSkipDedicatedFullScreenSlideForCurrentLoad(
            hasDedicatedFullScreenSlide,
            { ignoreLoadSkip: Boolean(options.ignoreDedicatedLoadSkip) }
        );

        if (shouldSkipDedicatedForCurrentLoad) {
            deps.markDedicatedFullScreenSlideCycleAsCompleted(state.fullScreenSlideConfigSignature);
            deps.stopDedicatedFullScreenSlideMode();
            deps.forceRestoreMainLayoutAfterDedicatedSlide();
        }

        if (hasDedicatedFullScreenSlide && state.forceFullScreenSlideModeActive) {
            if (deps.hasStaleDedicatedFullScreenSlideSession()) {
                deps.completeDedicatedFullScreenSlideCycle();
            }
            return;
        }

        // Fullscreen video has priority over dedicated fullscreen slide.
        if (hasDedicatedFullScreenSlide && !state.forceFullScreenSlideModeActive && deps.isVideoFullscreenModeActive()) {
            return;
        }

        if (hasDedicatedFullScreenSlide && !state.forceFullScreenSlideModeActive && !shouldSkipDedicatedForCurrentLoad) {
            deps.clearSidebarProductTimer();
            state.sidebarMixedModeSignature = '';
            state.sidebarProductPassedBeforeImages = false;
            state.sidebarMixedImageUrls = [];
            state.sidebarMixedImageIndex = 0;
            state.sidebarMixedVideoItems = [];
            state.sidebarMixedVideoIndex = 0;
            state.sidebarMixedTurnIndex = 0;
            state.sidebarMixedVideoTurnActive = false;
            state.sidebarMixedVideoTurnExpiresAt = 0;
            state.sidebarMixedCurrentVideoItem = null;
            state.sidebarMixedOnVideoFinished = null;
            const startedDedicatedSlide = deps.startDedicatedFullScreenSlideMode();
            if (startedDedicatedSlide) {
                return;
            }

            // If it did not start, continue with normal sidebar flow and avoid getting stuck.
            deps.markDedicatedFullScreenSlideCycleAsCompleted(state.fullScreenSlideConfigSignature);
        }

        deps.stopDedicatedFullScreenSlideMode();

        const isProductCarouselEnabled = deps.toBoolean(deps.visualConfig.rightSidebarProductCarouselEnabled, false);
        const transitionMode = String(deps.visualConfig.rightSidebarProductTransitionMode || 'products_only').toLowerCase();

        if (!deps.toBoolean(deps.visualConfig.showRightSidebarPanel, true)) {
            deps.clearSidebarProductTimer();
            state.sidebarMixedModeSignature = '';
            deps.hideSidebarProductCard();
            deps.stopImageSlideMode();
            deps.stopVideoPlaybackForImageMode();
            return;
        }

        if (isProductCarouselEnabled) {
            const sourceItems = Array.isArray(productsForSidebar) ? productsForSidebar : state.sidebarProductItems;

            if (transitionMode === 'before_images') {
                deps.startSidebarProductsBeforeImagesMode(sourceItems);
                return;
            }

            if (transitionMode === 'mixed_with_images') {
                deps.startSidebarProductsMixedWithImagesMode(sourceItems);
                return;
            }

            if (transitionMode === 'mixed_with_media') {
                deps.startSidebarProductsMixedWithMediaMode(sourceItems);
                return;
            }

            deps.startSidebarProductsOnlyMode(sourceItems);
            return;
        }

        deps.clearSidebarProductTimer();
        state.sidebarMixedModeSignature = '';
        deps.hideSidebarProductCard();
        state.sidebarProductPassedBeforeImages = false;
        state.sidebarMixedImageUrls = [];
        state.sidebarMixedImageIndex = 0;
        state.sidebarMixedVideoItems = [];
        state.sidebarMixedVideoIndex = 0;
        state.sidebarMixedTurnIndex = 0;
        state.sidebarMixedVideoTurnActive = false;
        state.sidebarMixedVideoTurnExpiresAt = 0;
        state.sidebarMixedCurrentVideoItem = null;
        state.sidebarMixedOnVideoFinished = null;

        const mode = api.getRightSidebarMediaType();

        if (mode === 'image') {
            state.rightSidebarHybridConfigSignature = '';
            state.rightSidebarHybridPhase = 'video';
            state.rightSidebarHybridVideoCountInPhase = 0;
            state.rightSidebarHybridImageCountInPhase = 0;
            state.rightSidebarHybridSwitching = false;
            state.rightSidebarHybridLastCompletedAt = 0;
            state.forceVideoPlaylistApplyOnce = false;
            state.rightSidebarHybridHasShownAnyImage = false;
            deps.startImageSlideMode();
            return;
        }

        if (mode === 'hybrid') {
            const hybridSignature = JSON.stringify({
                mode,
                imageUrls: String(deps.visualConfig.rightSidebarImageUrls || ''),
                imageInterval: Number(deps.visualConfig.rightSidebarImageInterval || 8),
                imageFit: String(deps.visualConfig.rightSidebarImageFit || 'scale-down'),
                imageHeight: Number(deps.visualConfig.rightSidebarImageHeight || 0),
                imageWidth: Number(deps.visualConfig.rightSidebarImageWidth || 0),
                videoCount: Number(deps.visualConfig.rightSidebarHybridVideoDuration || 2),
                imageCount: Number(deps.visualConfig.rightSidebarHybridImageDuration || 4),
                playlist: JSON.stringify(deps.parseConfiguredVideoUrls().map((item) => String(item?.url || ''))),
            });

            if (hybridSignature !== state.rightSidebarHybridConfigSignature) {
                state.rightSidebarHybridConfigSignature = hybridSignature;
                state.rightSidebarHybridPhase = 'video';
                state.rightSidebarHybridVideoCountInPhase = 0;
                state.rightSidebarHybridImageCountInPhase = 0;
                state.rightSidebarHybridSwitching = false;
                state.rightSidebarHybridLastCompletedAt = 0;
                state.forceVideoPlaylistApplyOnce = false;
                state.rightSidebarHybridHasShownAnyImage = false;
                await api.applyRightSidebarModeOnce(token, 'video');
                return;
            }

            if (state.rightSidebarHybridPhase === 'image') {
                deps.applyHybridImageLayout();

                const hasRunningImageTimer = Boolean(state.imageSlideTimer);
                if (!hasRunningImageTimer || !deps.tvImageSlide || deps.tvImageSlide.classList.contains('hidden')) {
                    deps.startHybridImagePhase(token);
                }

                return;
            }

            deps.stopImageSlideMode();
            await deps.loadVideoPlaylist(token);
            return;
        }

        state.rightSidebarHybridConfigSignature = '';
        state.rightSidebarHybridPhase = 'video';
        state.rightSidebarHybridVideoCountInPhase = 0;
        state.rightSidebarHybridImageCountInPhase = 0;
        state.rightSidebarHybridSwitching = false;
        state.rightSidebarHybridLastCompletedAt = 0;
        state.forceVideoPlaylistApplyOnce = false;
        state.rightSidebarHybridHasShownAnyImage = false;
        await api.applyRightSidebarModeOnce(token, 'video');
    };

    return api;
}
