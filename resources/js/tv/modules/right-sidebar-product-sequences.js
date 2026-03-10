export function createRightSidebarProductSequencesModule(deps) {
    const state = deps.state;
    const api = {};

    api.startSidebarProductsOnlyMode = function startSidebarProductsOnlyMode(items) {
        deps.clearSidebarProductTimer();
        state.sidebarMixedModeSignature = '';
        state.sidebarMixedVideoItems = [];
        state.sidebarMixedVideoIndex = 0;
        state.sidebarMixedTurnIndex = 0;
        state.sidebarMixedVideoTurnActive = false;
        state.sidebarMixedVideoTurnExpiresAt = 0;
        state.sidebarMixedCurrentVideoItem = null;
        state.sidebarMixedOnVideoFinished = null;
        deps.stopVideoPlaybackForImageMode();
        deps.stopImageSlideMode();

        state.sidebarProductItems = deps.normalizeSidebarProductItems(items);
        state.sidebarProductIndex = 0;

        if (state.sidebarProductItems.length === 0) {
            deps.hideSidebarProductCard();
            return;
        }

        deps.renderSidebarProductItem(state.sidebarProductItems[0]);
        const intervalSeconds = Math.max(1, Number(deps.visualConfig.rightSidebarProductInterval || 8));
        if (state.sidebarProductItems.length > 1) {
            state.sidebarProductTimer = setInterval(() => {
                state.sidebarProductIndex = (state.sidebarProductIndex + 1) % state.sidebarProductItems.length;
                deps.renderSidebarProductItem(state.sidebarProductItems[state.sidebarProductIndex]);
            }, intervalSeconds * 1000);
        }
    };

    api.startSidebarProductsBeforeImagesMode = function startSidebarProductsBeforeImagesMode(items) {
        deps.clearSidebarProductTimer();
        state.sidebarMixedModeSignature = '';
        state.sidebarMixedVideoItems = [];
        state.sidebarMixedVideoIndex = 0;
        state.sidebarMixedTurnIndex = 0;
        state.sidebarMixedVideoTurnActive = false;
        state.sidebarMixedVideoTurnExpiresAt = 0;
        state.sidebarMixedCurrentVideoItem = null;
        state.sidebarMixedOnVideoFinished = null;
        deps.stopVideoPlaybackForImageMode();
        deps.stopImageSlideMode();

        state.sidebarProductItems = deps.normalizeSidebarProductItems(items);
        state.sidebarProductIndex = 0;

        if (state.sidebarProductItems.length === 0) {
            deps.hideSidebarProductCard();
            deps.startImageSlideMode();
            return;
        }

        deps.renderSidebarProductItem(state.sidebarProductItems[0]);
        const intervalSeconds = Math.max(1, Number(deps.visualConfig.rightSidebarProductInterval || 8));
        if (state.sidebarProductItems.length === 1) {
            setTimeout(() => {
                deps.hideSidebarProductCard();
                deps.startImageSlideMode();
            }, intervalSeconds * 1000);
            return;
        }

        let shown = 1;
        state.sidebarProductTimer = setInterval(() => {
            state.sidebarProductIndex = (state.sidebarProductIndex + 1) % state.sidebarProductItems.length;
            shown += 1;
            deps.renderSidebarProductItem(state.sidebarProductItems[state.sidebarProductIndex]);

            if (shown >= state.sidebarProductItems.length) {
                deps.clearSidebarProductTimer();
                deps.hideSidebarProductCard();
                deps.startImageSlideMode();
            }
        }, intervalSeconds * 1000);
    };

    return api;
}
