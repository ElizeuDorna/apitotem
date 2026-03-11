export function createDedicatedFullScreenSlideModule(deps) {
    const api = {};
    const state = deps.state;

    api.parseConfiguredDedicatedFullScreenSlideUrls = function parseConfiguredDedicatedFullScreenSlideUrls() {
        if (!deps.toBoolean(deps.visualConfig.fullScreenSlideEnabled, false)) {
            return [];
        }

        const isAndroidRuntime = deps.isAndroidDevice();
        const enabledForPlatform = isAndroidRuntime
            ? deps.toBoolean(deps.visualConfig.fullScreenSlideEnabledAndroid, true)
            : deps.toBoolean(deps.visualConfig.fullScreenSlideEnabledWindows, true);
        if (!enabledForPlatform) {
            return [];
        }

        const today = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        const startDate = String(deps.visualConfig.fullScreenSlideStartDate || '').trim();
        const endDate = String(deps.visualConfig.fullScreenSlideEndDate || '').trim();

        if (startDate && today < startDate) {
            return [];
        }

        if (endDate && today > endDate) {
            return [];
        }

        const configured = deps.parseConfiguredUrlLines(deps.visualConfig.fullScreenSlideImageUrls);
        if (configured.length === 0) {
            return [];
        }

        const unique = [];
        const seen = new Set();

        configured.forEach((rawUrl) => {
            const normalized = String(rawUrl || '').trim();
            if (!normalized || seen.has(normalized)) {
                return;
            }

            seen.add(normalized);
            unique.push(normalized);
        });

        return unique;
    };

    api.buildDedicatedFullScreenSlideSignature = function buildDedicatedFullScreenSlideSignature() {
        const urls = api.parseConfiguredDedicatedFullScreenSlideUrls();
        const normalizedUrls = urls.map((item) => {
            const resolved = String(deps.resolveRenderableImageUrl(item) || item || '').trim();
            const withoutHash = resolved.split('#')[0] || '';
            const withoutQuery = withoutHash.split('?')[0] || '';
            return withoutQuery.replace(/\/+$/, '');
        });

        return JSON.stringify({
            urls: normalizedUrls,
            interval: Math.max(1, Number(deps.visualConfig.fullScreenSlideInterval || 8)),
        });
    };

    api.isDedicatedFullScreenSlideCompletedForSignature = function isDedicatedFullScreenSlideCompletedForSignature(signature) {
        const currentSignature = String(signature || '').trim();
        if (!currentSignature) {
            return false;
        }

        const storedCompletedSignature = deps.getStoredCompletedFullscreenSlideSignature();
        const memoryCompletedSignature = String(state.fullScreenSlideCompletedSignatureMemory || '').trim();
        const runtimeCompletedSignature = String(state.fullScreenSlideCompletedSignatureRuntime || '').trim();
        const windowNameCompletedSignature = String(deps.readCompletedSignatureFromWindowName() || '').trim();

        return (
            (storedCompletedSignature !== '' && storedCompletedSignature === currentSignature)
            || (memoryCompletedSignature !== '' && memoryCompletedSignature === currentSignature)
            || (runtimeCompletedSignature !== '' && runtimeCompletedSignature === currentSignature)
            || (windowNameCompletedSignature !== '' && windowNameCompletedSignature === currentSignature)
        );
    };

    api.markDedicatedFullScreenSlideCycleAsCompleted = function markDedicatedFullScreenSlideCycleAsCompleted(signature = '') {
        const resolvedSignature = String(signature || state.fullScreenSlideConfigSignature || '').trim();
        if (!resolvedSignature) {
            return;
        }

        state.hasCompletedDedicatedFullScreenSlideCycle = true;
        state.fullScreenSlideCompletedSignatureMemory = resolvedSignature;
        state.fullScreenSlideCompletedSignatureRuntime = resolvedSignature;
        deps.storeCompletedFullscreenSlideSignature(resolvedSignature);
    };

    api.shouldSkipDedicatedFullScreenSlideForCurrentLoad = function shouldSkipDedicatedFullScreenSlideForCurrentLoad(hasDedicatedFullScreenSlide) {
        if (!hasDedicatedFullScreenSlide) {
            return false;
        }

        if (state.forceFullScreenSlideModeActive) {
            return false;
        }

        return Number(state.dedicatedFullScreenCycleStartCount || 0) >= 1;
    };

    api.getDedicatedFullScreenSlideReturnDelaySeconds = function getDedicatedFullScreenSlideReturnDelaySeconds() {
        return Math.max(0, Math.min(86400, Number(deps.visualConfig.fullScreenSlideReturnDelaySeconds || 0)));
    };

    api.scheduleDedicatedFullScreenSlideRestart = function scheduleDedicatedFullScreenSlideRestart() {
        deps.clearFullScreenSlideReturnTimer();

        const delaySeconds = api.getDedicatedFullScreenSlideReturnDelaySeconds();
        if (delaySeconds <= 0) {
            return;
        }

        const hasDedicatedSlideUrls = api.parseConfiguredDedicatedFullScreenSlideUrls().length > 0;
        if (!hasDedicatedSlideUrls) {
            return;
        }

        const tryRestart = () => {
            if (state.forceFullScreenSlideModeActive) {
                return;
            }

            const hasSlideUrls = api.parseConfiguredDedicatedFullScreenSlideUrls().length > 0;
            if (!hasSlideUrls) {
                return;
            }

            if (deps.isVideoFullscreenModeActive()) {
                state.fullScreenSlideReturnTimer = setTimeout(tryRestart, 2000);
                return;
            }

            state.dedicatedFullScreenCycleStartCount = 0;
            state.hasCompletedDedicatedFullScreenSlideCycle = false;
            state.fullScreenSlideCompletedSignatureMemory = '';
            state.fullScreenSlideCompletedSignatureRuntime = '';
            deps.storeCompletedFullscreenSlideSignature('');

            const startedDedicatedSlide = api.startDedicatedFullScreenSlideMode();
            if (startedDedicatedSlide) {
                return;
            }

            const token = deps.getReliableDeviceToken();
            if (!token) {
                return;
            }

            const sidebarItems = Array.isArray(deps.getSidebarProductItems()) ? deps.getSidebarProductItems() : [];
            const latestItems = typeof deps.getLatestProductsForPagination === 'function'
                ? deps.getLatestProductsForPagination()
                : [];
            const resumeItems = sidebarItems.length > 0
                ? sidebarItems
                : (Array.isArray(latestItems) ? latestItems : []);

            deps.applyRightSidebarMediaMode(token, resumeItems, { ignoreDedicatedLoadSkip: true });
        };

        state.fullScreenSlideReturnTimer = setTimeout(tryRestart, delaySeconds * 1000);
    };

    api.completeDedicatedFullScreenSlideCycle = function completeDedicatedFullScreenSlideCycle() {
        if (!state.fullScreenSlideConfigSignature) {
            state.fullScreenSlideConfigSignature = api.buildDedicatedFullScreenSlideSignature();
        }

        api.markDedicatedFullScreenSlideCycleAsCompleted(state.fullScreenSlideConfigSignature);
        api.stopDedicatedFullScreenSlideMode();
        deps.forceRestoreMainLayoutAfterDedicatedSlide();
        deps.ensureRightSidebarMediaRestoredAfterDedicatedFullScreen();
        api.scheduleDedicatedFullScreenSlideRestart();

        const token = deps.getReliableDeviceToken();
        if (token) {
            const sidebarItems = Array.isArray(deps.getSidebarProductItems()) ? deps.getSidebarProductItems() : [];
            const latestItems = typeof deps.getLatestProductsForPagination === 'function'
                ? deps.getLatestProductsForPagination()
                : [];
            const resumeItems = sidebarItems.length > 0
                ? sidebarItems
                : (Array.isArray(latestItems) ? latestItems : []);

            deps.applyRightSidebarMediaMode(token, resumeItems);
        }

        deps.resumeExternalTimersForDedicatedFullScreen();
    };

    api.hasStaleDedicatedFullScreenSlideSession = function hasStaleDedicatedFullScreenSlideSession() {
        if (!state.forceFullScreenSlideModeActive) {
            return false;
        }

        const startedAt = Number(state.dedicatedFullScreenSlideStartedAt || 0);
        const maxExpectedMs = Math.max(1000, Number(state.dedicatedFullScreenSlideMaxExpectedMs || 0));
        const elapsedMs = startedAt > 0 ? (Date.now() - startedAt) : 0;
        const timedOut = startedAt > 0 && elapsedMs > (maxExpectedMs + 12000);
        const slideHidden = !deps.tvImageSlide || deps.tvImageSlide.classList.contains('hidden');
        const noRunningTimers = !state.fullScreenSlideTimer && !state.fullScreenSlideExitTimer;

        return timedOut || (slideHidden && noRunningTimers);
    };

    api.startDedicatedFullScreenSlideMode = function startDedicatedFullScreenSlideMode() {
        if (!deps.tvImageSlide) {
            return false;
        }

        if (deps.isVideoFullscreenModeActive()) {
            return false;
        }

        if (Number(state.dedicatedFullScreenCycleStartCount || 0) >= 1) {
            return false;
        }

        if (state.forceFullScreenSlideModeActive) {
            return true;
        }

        const nextUrls = api.parseConfiguredDedicatedFullScreenSlideUrls();
        if (nextUrls.length === 0) {
            api.stopDedicatedFullScreenSlideMode();
            return false;
        }

        if (!deps.shouldKeepRightSidebarVideoRunningDuringDedicatedFullScreen()) {
            deps.stopVideoPlaybackForImageMode();
        }
        deps.pauseExternalTimersForDedicatedFullScreen();
        deps.clearImageSlideTimer();
        deps.clearFullScreenSlideTimer();
        deps.hideSidebarProductCard();
        deps.hideSlideTextOverlays();

        state.forceFullScreenSlideModeActive = true;
        state.hasCompletedDedicatedFullScreenSlideCycle = false;
        state.dedicatedFullScreenCycleStartCount = Number(state.dedicatedFullScreenCycleStartCount || 0) + 1;
        state.dedicatedFullScreenSlideStartedAt = Date.now();
        state.fullScreenSlideUrls = nextUrls;
        state.fullScreenSlideIndex = 0;
        state.imageSlideUrls = state.fullScreenSlideUrls.slice();
        state.imageSlideSettingsByUrl = new Map();

        deps.tvImageSlide.classList.remove('hidden');
        deps.showImageSlideAt(0);

        const intervalSeconds = Math.max(1, Number(deps.visualConfig.fullScreenSlideInterval || 8));
        const hardStopMs = Math.max(1000, state.fullScreenSlideUrls.length * intervalSeconds * 1000 + 1500);
        state.dedicatedFullScreenSlideMaxExpectedMs = hardStopMs;

        deps.clearFullScreenSlideExitTimer();

        const finishCycle = () => {
            deps.clearFullScreenSlideExitTimer();
            api.completeDedicatedFullScreenSlideCycle();
        };

        const validateCycleCompletion = () => {
            if (!state.forceFullScreenSlideModeActive) {
                return;
            }

            const lastIndex = Math.max(0, state.fullScreenSlideUrls.length - 1);
            const reachedLastSlide = Number(state.fullScreenSlideIndex || 0) >= lastIndex;
            const hasPendingAdvance = Boolean(state.fullScreenSlideTimer);

            if (reachedLastSlide && !hasPendingAdvance) {
                finishCycle();
                return;
            }

            state.fullScreenSlideExitTimer = setTimeout(validateCycleCompletion, 2000);
        };

        state.fullScreenSlideExitTimer = setTimeout(validateCycleCompletion, hardStopMs);

        const advanceDedicatedSlideStep = () => {
            deps.clearFullScreenSlideTimer();

            state.fullScreenSlideTimer = setTimeout(() => {
                if (!state.forceFullScreenSlideModeActive) {
                    return;
                }

                const nextIndex = Number(state.fullScreenSlideIndex || 0) + 1;
                if (nextIndex >= state.fullScreenSlideUrls.length) {
                    finishCycle();
                    return;
                }

                state.fullScreenSlideIndex = nextIndex;
                state.imageSlideUrls = state.fullScreenSlideUrls.slice();
                deps.showImageSlideAt(state.fullScreenSlideIndex);
                advanceDedicatedSlideStep();
            }, intervalSeconds * 1000);
        };

        if (state.fullScreenSlideUrls.length > 1) {
            advanceDedicatedSlideStep();
        } else {
            state.fullScreenSlideTimer = setTimeout(() => {
                if (!state.forceFullScreenSlideModeActive) {
                    return;
                }

                finishCycle();
            }, intervalSeconds * 1000);
        }

        return true;
    };

    api.stopDedicatedFullScreenSlideMode = function stopDedicatedFullScreenSlideMode() {
        deps.clearFullScreenSlideTimer();
        deps.clearFullScreenSlideExitTimer();

        state.fullScreenSlideUrls = [];
        state.fullScreenSlideIndex = 0;
        state.dedicatedFullScreenSlideStartedAt = 0;
        state.dedicatedFullScreenSlideMaxExpectedMs = 0;
        state.forceFullScreenSlideModeActive = false;
        state.imageSlideUrls = [];
        state.imageSlideSettingsByUrl = new Map();

        deps.setImageSlideFullscreenMode(false);

        if (deps.tvImageSlide) {
            deps.tvImageSlide.classList.add('hidden');
            deps.tvImageSlide.removeAttribute('src');
            deps.tvImageSlide.removeAttribute('data-fallback-candidates');
            deps.tvImageSlide.removeAttribute('data-fallback-index');
        }

        deps.hideSlideTextOverlays();
    };

    api.syncDedicatedFullScreenCompletionState = function syncDedicatedFullScreenCompletionState() {
        const currentSignature = String(state.fullScreenSlideConfigSignature || '').trim();
        if (!currentSignature) {
            state.hasCompletedDedicatedFullScreenSlideCycle = false;
            return;
        }

        state.hasCompletedDedicatedFullScreenSlideCycle = api.isDedicatedFullScreenSlideCompletedForSignature(currentSignature);
    };

    return api;
}
