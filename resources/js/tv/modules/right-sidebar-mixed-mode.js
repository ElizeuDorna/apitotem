export function createRightSidebarMixedModeModule(deps) {
    const state = deps.state;
    const api = {};

    api.startSidebarProductsMixedWithImagesMode = function startSidebarProductsMixedWithImagesMode(items) {
        api.startSidebarProductsMixedMode(items, false);
    };

    api.startSidebarProductsMixedWithMediaMode = function startSidebarProductsMixedWithMediaMode(items) {
        api.startSidebarProductsMixedMode(items, true);
    };

    api.startSidebarProductsMixedMode = function startSidebarProductsMixedMode(items, includeVideos) {
        const nextProductItems = deps.normalizeSidebarProductItems(items);
        const nextImageUrls = deps.parseConfiguredImageSlideUrls();
        const nextImageSettingsMap = new Map(state.imageSlideSettingsByUrl);
        const nextVideoItems = includeVideos && deps.toBoolean(deps.visualConfig.showVideoPanel, true)
            ? deps.parseConfiguredVideoUrls()
            : [];
        const intervalSeconds = Math.max(1, Number(deps.visualConfig.rightSidebarProductInterval || 8));
        const nextSignature = JSON.stringify({
            intervalSeconds,
            transition: String(deps.visualConfig.rightSidebarProductTransitionMode || (includeVideos ? 'mixed_with_media' : 'mixed_with_images')).toLowerCase(),
            playbackSequence: String(deps.visualConfig.rightSidebarPlaybackSequence || 'products,image,video').toLowerCase(),
            displayMode: String(deps.visualConfig.rightSidebarProductDisplayMode || 'all').toLowerCase(),
            products: nextProductItems.map((item) => ({
                id: Number(item?.id || 0),
                nome: String(item?.nome || ''),
                preco: Number(item?.preco || 0),
                oferta: Number(item?.oferta || 0),
                imagem: String(item?.imagem || ''),
            })),
            images: nextImageUrls,
            videos: nextVideoItems.map((item) => ({
                url: String(item?.url || ''),
                muted: deps.toBoolean(item?.muted, false),
                durationSeconds: Math.max(0, Number(item?.durationSeconds || 0)),
                heightPx: Math.max(0, Number(item?.heightPx || 0)),
            })),
        });

        const isSameMixedSessionRunning = state.sidebarMixedModeSignature !== ''
            && state.sidebarMixedModeSignature === nextSignature
            && (Boolean(state.sidebarProductTimer) || state.sidebarMixedVideoTurnActive);

        state.sidebarProductItems = nextProductItems;
        state.sidebarMixedImageUrls = nextImageUrls;
        state.imageSlideUrls = state.sidebarMixedImageUrls;
        state.sidebarMixedVideoItems = nextVideoItems;

        if (isSameMixedSessionRunning) {
            // Keep current turn/index so refresh polling does not restart in product turn only.
            return;
        }

        deps.clearSidebarProductTimer();
        deps.stopVideoPlaybackForImageMode();
        deps.stopImageSlideMode();

        // stopImageSlideMode clears slide arrays/maps; restore mixed-mode image source state.
        state.imageSlideUrls = Array.isArray(state.sidebarMixedImageUrls) ? state.sidebarMixedImageUrls.slice() : [];
        state.imageSlideSettingsByUrl = nextImageSettingsMap;

        state.sidebarMixedModeSignature = nextSignature;
        state.sidebarProductIndex = 0;
        state.sidebarMixedImageIndex = 0;
        state.sidebarMixedVideoIndex = 0;
        state.sidebarMixedTurnIndex = 0;

        if (state.sidebarProductItems.length === 0 && state.sidebarMixedImageUrls.length === 0 && state.sidebarMixedVideoItems.length === 0) {
            deps.hideSidebarProductCard();
            return;
        }

        const parseConfiguredTurnSequence = () => {
            const rawSequence = String(deps.visualConfig.rightSidebarPlaybackSequence || 'products,image,video')
                .trim()
                .toLowerCase();
            const aliases = {
                product: 'product',
                products: 'product',
                image: 'image',
                images: 'image',
                slide: 'image',
                slides: 'image',
                video: 'video',
                videos: 'video',
            };

            const parsed = rawSequence
                .split(',')
                .map((part) => aliases[String(part || '').trim()] || '')
                .filter((part) => part !== '');

            const uniqueTurns = [];
            parsed.forEach((turn) => {
                if (!uniqueTurns.includes(turn)) {
                    uniqueTurns.push(turn);
                }
            });

            if (!includeVideos) {
                return uniqueTurns.filter((turn) => turn !== 'video');
            }

            return uniqueTurns;
        };

        const configuredTurnSequence = parseConfiguredTurnSequence();
        const availableTurns = {
            product: state.sidebarProductItems.length > 0,
            image: state.sidebarMixedImageUrls.length > 0,
            video: state.sidebarMixedVideoItems.length > 0,
        };

        let turnSequence = configuredTurnSequence.filter((turn) => availableTurns[turn] === true);
        if (turnSequence.length === 0) {
            turnSequence = ['product', 'image', 'video'].filter((turn) => availableTurns[turn] === true);
        }

        if (turnSequence.length === 0) {
            deps.hideSidebarProductCard();
            return;
        }

        const renderMixedTurn = () => {
            const turn = turnSequence[state.sidebarMixedTurnIndex % turnSequence.length] || 'product';

            if (turn === 'product' && state.sidebarProductItems.length > 0) {
                state.sidebarMixedVideoTurnActive = false;
                state.sidebarMixedVideoTurnExpiresAt = 0;
                state.sidebarMixedCurrentVideoItem = null;
                deps.stopVideoPlaybackForImageMode();
                if (deps.tvImageSlide) {
                    deps.tvImageSlide.classList.add('hidden');
                }
                deps.hideSlideTextOverlays();

                deps.renderSidebarProductItem(state.sidebarProductItems[state.sidebarProductIndex]);
                state.sidebarProductIndex += 1;
                if (state.sidebarProductIndex >= state.sidebarProductItems.length) {
                    state.sidebarProductIndex = 0;
                    state.sidebarMixedTurnIndex = (state.sidebarMixedTurnIndex + 1) % turnSequence.length;
                }

                scheduleNextMixedTurn(intervalSeconds);
                return;
            }

            if (turn === 'image' && state.sidebarMixedImageUrls.length > 0) {
                state.sidebarMixedVideoTurnActive = false;
                state.sidebarMixedVideoTurnExpiresAt = 0;
                state.sidebarMixedCurrentVideoItem = null;
                deps.stopVideoPlaybackForImageMode();
                deps.hideSidebarProductCard();
                state.imageSlideUrls = Array.isArray(state.sidebarMixedImageUrls) ? state.sidebarMixedImageUrls.slice() : [];
                if (deps.tvImageSlide) {
                    deps.tvImageSlide.classList.remove('hidden');
                    deps.showImageSlideAt(state.sidebarMixedImageIndex);
                }

                state.sidebarMixedImageIndex += 1;
                if (state.sidebarMixedImageIndex >= state.sidebarMixedImageUrls.length) {
                    state.sidebarMixedImageIndex = 0;
                    state.sidebarMixedTurnIndex = (state.sidebarMixedTurnIndex + 1) % turnSequence.length;
                }

                scheduleNextMixedTurn(intervalSeconds);
                return;
            }

            if (turn === 'video' && state.sidebarMixedVideoItems.length > 0) {
                deps.hideSidebarProductCard();
                deps.hideSlideTextOverlays();
                if (deps.tvImageSlide) {
                    deps.tvImageSlide.classList.add('hidden');
                }

                const nextVideo = state.sidebarMixedVideoItems[state.sidebarMixedVideoIndex % state.sidebarMixedVideoItems.length] || null;
                if (nextVideo?.url) {
                    deps.applyVideoSource(nextVideo);
                }

                const currentUrl = String(nextVideo?.url || '').trim();
                const isYouTube = Boolean(deps.getYouTubeVideoId(currentUrl));
                const isDirectVideoFile = /\.(mp4|webm|ogg|m3u8)([?#].*)?$/i.test(currentUrl);
                const hasEmbedUrl = Boolean(deps.resolveEmbedUrl(currentUrl, deps.toBoolean(nextVideo?.muted, false)));
                const needsFallbackTimer = hasEmbedUrl && !isYouTube && !isDirectVideoFile;

                state.sidebarMixedVideoTurnActive = true;
                state.sidebarMixedVideoTurnExpiresAt = 0;
                state.sidebarMixedCurrentVideoItem = nextVideo || null;

                if (needsFallbackTimer) {
                    // Non-YouTube embeds usually do not emit reliable ended events.
                    const configuredVideoSeconds = Math.max(0, Number(nextVideo?.durationSeconds || 0));
                    const fallbackSeconds = Math.max(configuredVideoSeconds, 30);
                    deps.clearSidebarProductTimer();
                    state.sidebarProductTimer = setTimeout(() => {
                        if (typeof state.sidebarMixedOnVideoFinished === 'function') {
                            state.sidebarMixedOnVideoFinished();
                        }
                    }, fallbackSeconds * 1000);
                } else {
                    deps.clearSidebarProductTimer();
                }

                return;
            }

            // If current phase has no items, skip to next phase.
            state.sidebarMixedTurnIndex = (state.sidebarMixedTurnIndex + 1) % turnSequence.length;
            state.sidebarMixedVideoTurnActive = false;
            state.sidebarMixedVideoTurnExpiresAt = 0;
            state.sidebarMixedCurrentVideoItem = null;
            scheduleNextMixedTurn(intervalSeconds);
        };

        state.sidebarMixedOnVideoFinished = () => {
            if (!state.sidebarMixedVideoTurnActive || state.sidebarMixedVideoItems.length === 0) {
                return false;
            }

            deps.clearSidebarProductTimer();

            if (state.sidebarMixedVideoIndex < state.sidebarMixedVideoItems.length - 1) {
                state.sidebarMixedVideoIndex += 1;
                renderMixedTurn();
                return true;
            }

            state.sidebarMixedVideoIndex = 0;
            state.sidebarMixedVideoTurnActive = false;
            state.sidebarMixedVideoTurnExpiresAt = 0;
            state.sidebarMixedCurrentVideoItem = null;
            state.sidebarMixedTurnIndex = (state.sidebarMixedTurnIndex + 1) % turnSequence.length;
            renderMixedTurn();
            return true;
        };

        const scheduleNextMixedTurn = (delaySeconds = intervalSeconds) => {
            deps.clearSidebarProductTimer();
            state.sidebarProductTimer = setTimeout(() => {
                renderMixedTurn();
            }, Math.max(1, Number(delaySeconds) || intervalSeconds) * 1000);
        };

        renderMixedTurn();
    };

    return api;
}
