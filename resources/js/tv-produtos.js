const tokenInput = document.getElementById('deviceToken');
const loadButton = document.getElementById('loadProducts');
const statusMessage = document.getElementById('statusMessage');
const totalProducts = document.getElementById('totalProducts');
const productsGroupLabel = document.getElementById('productsGroupLabel');
const productsGrid = document.getElementById('productsGrid');
const emptyState = document.getElementById('emptyState');
const tvVideo = document.getElementById('tvVideo');
const tvEmbed = document.getElementById('tvEmbed');
const videoHint = document.getElementById('videoHint');
const tvHeader = document.getElementById('tvHeader');
const tvProductsPanel = document.getElementById('tvProductsPanel');
const tvVideoPanel = document.getElementById('tvVideoPanel');
const tvMain = document.getElementById('tvMain');

const queryParams = new URLSearchParams(window.location.search);
const initialToken = queryParams.get('token') || localStorage.getItem('tv_device_token') || '';
const apiEndpoint = localStorage.getItem('tv_api_endpoint') || queryParams.get('api') || '/api/tv/produtos';
const configEndpoint = '/api/tv/telaweb01/config';
const mediaEndpoint = '/api/tv/midias';
const configPageUrl = '/tv/telaweb01/configuracao';
const refreshSeconds = Number(localStorage.getItem('tv_refresh_seconds') || queryParams.get('refresh') || 30);
const AUDIO_UNLOCK_STORAGE_KEY = 'tv_audio_autoplay_unlocked';

const visualConfig = {
    videoUrl: '',
    videoMuted: false,
    videoPlaylist: [],
    showVideoPanel: true,
    appBackgroundColor: '#020617',
    productsPanelBackgroundColor: '#0f172a',
    listBorderColor: '#334155',
    listBorderWidth: 1,
    videoBackgroundColor: '#000000',
    showRightSidebarBorder: true,
    rightSidebarBorderColor: '#334155',
    rightSidebarBorderWidth: 1,
    isVideoPanelTransparent: false,
    rowBackgroundColor: '#020617',
    borderColor: '#334155',
    rowBorderWidth: 1,
    isRowBorderTransparent: false,
    priceColor: '#818cf8',
    showBorder: true,
    showTitle: true,
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
    listFontSize: 16,
    groupLabelFontSize: 14,
    groupLabelColor: '#cbd5e1',
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
let videoFallbackTimer = null;
let initialVideoAutoplayRetryTimer = null;
let audioAutoplayUnlocked = localStorage.getItem(AUDIO_UNLOCK_STORAGE_KEY) === '1';
let forceMuteOnFirstPlayback = true;

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
                fullscreen: toBoolean(item?.fullscreen, false),
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
        .map((url) => ({ url, muted: Boolean(visualConfig.videoMuted), active: true, fullscreen: false, durationSeconds: 0, heightPx: 0 }));
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
    const itemFullscreen = toBoolean(typeof videoItem === 'object' ? videoItem?.fullscreen : false, false);
    const itemDurationSeconds = Math.max(0, Number(typeof videoItem === 'object' ? videoItem?.durationSeconds : 0) || 0);
    const itemHeightPx = Math.max(0, Number(typeof videoItem === 'object' ? videoItem?.heightPx : 0) || 0);
    const youTubeVideoId = getYouTubeVideoId(videoUrl);
    const embedUrl = resolveEmbedUrl(videoUrl, effectiveMuted);

    if (shouldForceFirstPlaybackMuted) {
        forceMuteOnFirstPlayback = false;
    }

    clearVideoFallbackTimer();
    document.body.classList.toggle('tv-video-fullscreen', itemFullscreen);
    applyCurrentVideoHeight(itemFullscreen ? 0 : itemHeightPx);

    if (itemFullscreen) {
        setTimeout(() => {
            if (tvVideo && !tvVideo.classList.contains('hidden') && tvVideo.paused) {
                playHtmlVideoWithAutoplayFallback(effectiveMuted);
            }

            if (tvEmbed && !tvEmbed.classList.contains('hidden') && youTubePlayer && typeof youTubePlayer.playVideo === 'function') {
                try {
                    youTubePlayer.playVideo();
                } catch (_error) {
                }
            }
        }, 250);
    }

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
                fullscreen: toBoolean(item?.fullscreen, false),
                durationSeconds: Math.max(0, Number(item?.durationSeconds || 0)),
                heightPx: Math.max(0, Number(item?.heightPx || 0)),
            };
        })
        .filter((item) => item.url !== '' && item.active);

    const nextSignature = JSON.stringify(list.map((item) => ({
        url: item.url,
        muted: item.muted,
        active: item.active,
        fullscreen: item.fullscreen,
        durationSeconds: item.durationSeconds,
        heightPx: item.heightPx,
    })));

    if (nextSignature === currentVideoPlaylistSignature && videoPlaylistItems.length > 0) {
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
    localStorage.setItem('tv_device_token', initialToken);
} else if (window.location.pathname !== configPageUrl) {
    window.location.replace(configPageUrl);
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

    statusMessage.textContent = message;
    statusMessage.className = `text-xs ${isError ? 'text-red-400' : 'text-slate-400'}`;
}

function applyTitleVisibility() {
    if (!tvHeader) {
        return;
    }

    tvHeader.style.display = visualConfig.showTitle ? '' : 'none';
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

function applyVideoBackground() {
    const color = visualConfig.videoBackgroundColor || '#000000';
    const resolvedColor = visualConfig.isVideoPanelTransparent ? 'transparent' : color;

    if (tvVideoPanel) {
        tvVideoPanel.style.backgroundColor = resolvedColor;
    }

    if (tvVideo) {
        tvVideo.style.backgroundColor = resolvedColor;
    }

    if (tvEmbed) {
        tvEmbed.style.backgroundColor = resolvedColor;
    }
}

function applyRightSidebarBorder() {
    if (!tvVideoPanel) {
        return;
    }

    const rightSidebarBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rightSidebarBorderWidth ?? 1)));
    const shouldShowRightSidebarBorder = Boolean(visualConfig.showRightSidebarBorder) && rightSidebarBorderWidth > 0;

    if (!shouldShowRightSidebarBorder) {
        tvVideoPanel.style.setProperty('border-width', '0', 'important');
        tvVideoPanel.style.setProperty('border-style', 'none', 'important');
        tvVideoPanel.style.setProperty('border-color', 'transparent', 'important');
        return;
    }

    tvVideoPanel.style.setProperty('border-style', 'solid', 'important');
    tvVideoPanel.style.setProperty('border-width', `${rightSidebarBorderWidth}px`, 'important');
    tvVideoPanel.style.setProperty('border-color', visualConfig.rightSidebarBorderColor || '#334155', 'important');
}

function applyVideoPanelVisibility() {
    if (!tvVideoPanel || !tvMain) {
        return;
    }

    if (!visualConfig.showVideoPanel) {
        tvVideoPanel.style.display = 'none';
        tvMain.style.gridTemplateColumns = '1fr';
        return;
    }

    tvVideoPanel.style.display = '';
    tvMain.style.gridTemplateColumns = '';
}

function renderProducts(produtos) {
    productsGrid.innerHTML = '';

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

    if (productsGroupLabel) {
        const groupLabelFontSize = Math.min(60, Math.max(10, Number(visualConfig.groupLabelFontSize || 14)));
        productsGroupLabel.style.fontSize = `${groupLabelFontSize}px`;
        productsGroupLabel.style.color = visualConfig.groupLabelColor || '#cbd5e1';

        const groupNames = Array.from(new Set(
            produtos
                .map((item) => String(item?.grupo?.nome || '').trim())
                .filter(Boolean)
        ));

        if (groupNames.length === 1) {
            productsGroupLabel.textContent = `${groupNames[0]}`;
        } else if (groupNames.length > 1) {
            productsGroupLabel.textContent = `${groupNames.join(' • ')}`;
        } else {
            productsGroupLabel.textContent = 'Grupo não informado';
        }
    }

    emptyState.classList.add('hidden');
    if (totalProducts) {
        totalProducts.textContent = `${produtos.length} produtos`;
    }

    for (const item of produtos) {
        const row = document.createElement('article');
        row.className = 'rounded-lg bg-slate-950 px-4 py-3';

        const rowBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rowBorderWidth ?? 1)));
        const shouldShowRowBorder = Boolean(visualConfig.showBorder) && !visualConfig.isRowBorderTransparent && rowBorderWidth > 0;
        row.style.borderStyle = shouldShowRowBorder ? 'solid' : 'none';
        row.style.borderWidth = shouldShowRowBorder ? `${rowBorderWidth}px` : '0';
        row.style.borderColor = shouldShowRowBorder ? (visualConfig.borderColor || '#334155') : 'transparent';
        row.style.backgroundColor = visualConfig.rowBackgroundColor;
        if (visualConfig.useGradient) {
            row.style.backgroundImage = `linear-gradient(to bottom, ${visualConfig.gradientStartColor}, ${visualConfig.gradientEndColor})`;
        } else {
            row.style.backgroundImage = 'none';
        }

        const ofertaValue = Number(item.oferta);
        const hasOferta = Number.isFinite(ofertaValue) && ofertaValue >= 1;
        const price = hasOferta ? ofertaValue : item.preco;
        const baseFontSize = Math.min(60, Math.max(10, Number(visualConfig.listFontSize || 16)));
        const imageUrl = String(item.imagem || '').trim();
        const imageWidth = Math.min(400, Math.max(20, Number(visualConfig.imageWidth || 56)));
        const imageHeight = Math.min(400, Math.max(20, Number(visualConfig.imageHeight || 56)));
        const imageMarkup = visualConfig.showImage && imageUrl
            ? `<img src="${imageUrl}" alt="${item.nome ?? 'Produto'}" class="rounded object-cover shrink-0" style="width:${imageWidth}px;height:${imageHeight}px" loading="lazy" onerror="this.style.display='none'">`
            : '';

        row.innerHTML = `
            <div class="flex items-center gap-3">
                ${imageMarkup}
                <h3 class="font-semibold flex-1" style="color:${visualConfig.priceColor};font-size:${baseFontSize}px">${item.nome ?? 'Sem nome'}</h3>
                <p class="font-semibold" style="color:${visualConfig.priceColor};font-size:${baseFontSize}px">${formatPrice(price)}</p>
            </div>
        `;

        productsGrid.appendChild(row);
    }
}

function clearPaginationTimer() {
    if (paginationTimer) {
        clearInterval(paginationTimer);
        paginationTimer = null;
    }
}

function renderProductsWithPagination(produtos) {
    clearPaginationTimer();

    const list = Array.isArray(produtos) ? produtos : [];

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
        return;
    }

    try {
        const response = await fetch(configEndpoint, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        const payload = await response.json();

        if (!response.ok || !payload?.success) {
            return;
        }

        Object.assign(visualConfig, payload.data || {});
        visualConfig.showImage = Boolean(visualConfig.showImage);
        visualConfig.rowBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rowBorderWidth ?? 1)));
        visualConfig.listBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.listBorderWidth ?? 1)));
        visualConfig.rightSidebarBorderWidth = Math.min(20, Math.max(0, Number(visualConfig.rightSidebarBorderWidth ?? 1)));

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
        applyVideoBackground();
        applyRightSidebarBorder();
        applyVideoPanelVisibility();
        applyTitleVisibility();
    } catch (_error) {
    }
}

async function loadProducts() {
    const token = queryParams.get('token') || localStorage.getItem('tv_device_token') || '';

    if (!token) {
        clearPaginationTimer();
        window.location.replace(configPageUrl);
        return;
    }

    updateStatus('Carregando produtos...');

    await loadVisualConfig(token);
    await loadVideoPlaylist(token);

    try {
        const response = await fetch(apiEndpoint, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
            },
        });

        const payload = await response.json();

        if (!response.ok || !payload?.success) {
            throw new Error('Falha ao consultar API de produtos da TV.');
        }

        const produtos = payload?.data?.produtos ?? [];
        renderProductsWithPagination(produtos);
        updateStatus(`Atualizado em ${new Date().toLocaleTimeString('pt-BR')}.`);
    } catch (error) {
        clearPaginationTimer();
        renderProducts([]);
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

if (initialToken) {
    loadProducts();
}

if (refreshSeconds > 0) {
    setInterval(() => {
        if (localStorage.getItem('tv_device_token')) {
            loadProducts();
        }
    }, refreshSeconds * 1000);
}

if (tvVideo) {
    tvVideo.addEventListener('loadedmetadata', () => ensureVideoAudioEnabled(tvVideo.muted));
    tvVideo.addEventListener('play', () => ensureVideoAudioEnabled(tvVideo.muted));
    tvVideo.addEventListener('ended', () => {
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

    if (finished && videoPlaylistItems.length > 1) {
        clearVideoFallbackTimer();
        playNextVideoInPlaylist();
    }
});
