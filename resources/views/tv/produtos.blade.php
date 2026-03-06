<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>TV Produtos</title>
    @vite(['resources/css/app.css', 'resources/js/tv-produtos.js'])
    <style>
        html, body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            width: 100%;
            height: 100%;
            background: #000;
        }

        body {
            font-size: clamp(14px, 1.1vw, 20px);
        }

        .tv-shell {
            box-sizing: border-box;
            width: 100vw;
            height: 100vh;
            padding: clamp(8px, 1.2vw, 20px);
            display: flex;
            flex-direction: column;
        }

        .tv-main {
            display: grid;
            grid-template-columns: 1fr;
            gap: clamp(10px, 1.2vw, 20px);
            flex: 1;
            min-height: 0;
        }

        @media (min-width: 1024px) {
            .tv-main {
                grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
            }
        }

        .tv-panel {
            min-height: 0;
            height: 100%;
            max-height: none;
            overflow: hidden;
        }

        #tvVideoPanel {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            gap: 10px;
        }

        #tvRightSidebarLogoSlot {
            flex: 0 0 auto;
            min-height: 58px;
            height: 58px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(15, 23, 42, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        #tvRightSidebarLogoSlot.is-placeholder::after {
            content: 'LOGO';
            font-size: 12px;
            letter-spacing: 0.08em;
            color: rgba(148, 163, 184, 0.7);
        }

        #tvRightSidebarLogo {
            max-width: 90%;
            max-height: 46px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        #tvRightSidebarMediaWrap {
            flex: 1;
            min-height: 0;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }

        #tvLeftVerticalLogoSlot {
            position: fixed;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 45;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(15, 23, 42, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 4px;
        }

        #tvLeftVerticalLogoSlot.is-placeholder::after {
            content: 'LOGO';
            font-size: 12px;
            letter-spacing: 0.08em;
            color: rgba(148, 163, 184, 0.7);
        }

        #tvLeftVerticalLogo {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        #tvVideo,
        #tvEmbed,
        #tvImageSlide {
            width: 100%;
            height: 100%;
            min-height: 0;
            object-fit: contain;
            object-position: center top;
        }

        #tvEmbed {
            aspect-ratio: auto;
        }

        .tv-title-container {
            width: 100%;
            overflow: hidden;
        }

        .tv-title-text {
            display: inline-block;
            white-space: nowrap;
            max-width: 100%;
            vertical-align: middle;
        }

        .tv-title-text.is-dynamic {
            max-width: none;
            padding-left: 100%;
            animation-name: tv-title-marquee;
            animation-timing-function: linear;
            animation-iteration-count: infinite;
        }

        @keyframes tv-title-marquee {
            from {
                transform: translateX(0%);
            }

            to {
                transform: translateX(-100%);
            }
        }

        .tv-video-fullscreen #tvHeader,
        .tv-video-fullscreen #tvFooter,
        .tv-video-fullscreen #tvProductsPanel {
            display: none;
        }

        .tv-video-fullscreen #tvMain {
            display: block;
        }

        .tv-video-fullscreen #tvVideoPanel {
            position: fixed;
            inset: 0;
            z-index: 50;
            margin: 0;
            border: none;
            border-radius: 0;
            max-height: none;
            min-height: 100vh;
            width: 100vw;
            padding: 0;
            background: #000;
            gap: 0;
        }

        .tv-video-fullscreen #tvRightSidebarLogoSlot {
            display: none;
        }

        .tv-video-fullscreen #tvRightSidebarMediaWrap {
            width: 100vw;
            height: 100vh;
        }

        .tv-video-fullscreen #tvVideo,
        .tv-video-fullscreen #tvEmbed,
        .tv-video-fullscreen #tvImageSlide {
            width: 100vw;
            height: 100vh;
            border-radius: 0;
        }

        .tv-video-fullscreen #videoHint,
        .tv-video-fullscreen #tvVideoPanel h2,
        .tv-video-fullscreen #tvVideoPanel p {
            display: none;
        }

        .tv-fullscreen-test-btn {
            position: fixed;
            left: 10px;
            bottom: 10px;
            z-index: 70;
            width: 18px;
            height: 18px;
            border: 1px solid rgba(148, 163, 184, 0.55);
            background: rgba(15, 23, 42, 0.45);
            color: rgba(226, 232, 240, 0.88);
            font-size: 11px;
            line-height: 1;
            padding: 0;
            border-radius: 9999px;
            cursor: pointer;
            backdrop-filter: blur(1px);
            opacity: 0.72;
            transition: opacity 0.2s ease, transform 0.2s ease, width 0.2s ease, height 0.2s ease, box-shadow 0.2s ease;
        }

        .tv-fullscreen-test-btn:hover {
            opacity: 0.95;
            transform: scale(1.05);
        }

        .tv-fullscreen-test-btn:focus-visible {
            outline: 2px solid #93c5fd;
            outline-offset: 2px;
        }

        .tv-fullscreen-test-btn.is-hidden-soft {
            opacity: 0.12;
            transform: scale(0.9);
        }

        .tv-fullscreen-test-btn.is-highlight {
            width: 28px;
            height: 28px;
            font-size: 16px;
            opacity: 0.98;
            border-color: rgba(191, 219, 254, 0.95);
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.45);
        }

        .tv-status-floating {
            position: fixed;
            left: 12px;
            bottom: 56px;
            z-index: 70;
            max-width: min(60vw, 520px);
            border: 1px solid #1e293b;
            background: rgba(2, 6, 23, 0.9);
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.3;
            padding: 8px 10px;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div id="tvShell" class="tv-shell">
        <header id="tvHeader" class="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
            <div id="tvHeaderTitleContainer" class="tv-title-container">
                <h1 id="tvHeaderTitle" class="tv-title-text text-2xl md:text-3xl font-semibold tracking-tight">Lista de Produtos (TV)</h1>
            </div>
        </header>

        <main id="tvMain" class="tv-main">
            <section id="tvProductsPanel" class="tv-panel rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div class="mb-3">
                    <p id="productsGroupLabel" class="text-sm font-medium text-slate-300"></p>
                </div>
                <div id="productsGrid" class="grid grid-cols-1 gap-3"></div>
                <p id="emptyState" class="hidden rounded-md border border-slate-700 bg-slate-950 p-4 text-sm text-slate-300">
                    Nenhum produto disponível para o token informado.
                </p>
            </section>

            <aside id="tvVideoPanel" class="tv-panel rounded-xl border border-slate-800 bg-slate-900 p-4">
                <div id="tvRightSidebarLogoSlot" class="hidden is-placeholder">
                    <img id="tvRightSidebarLogo" class="hidden" alt="Logo da empresa" loading="eager">
                </div>

                <div id="tvRightSidebarMediaWrap">
                    <video id="tvVideo" class="w-full rounded-lg bg-black" controls autoplay playsinline>
                        <source src="/tv/videos/demo.mp4" type="video/mp4">
                        Seu navegador não suporta vídeo HTML5.
                    </video>
                    <iframe id="tvEmbed" class="hidden w-full rounded-lg bg-black" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                    <img id="tvImageSlide" class="hidden w-full rounded-lg bg-black" alt="Slide lateral" loading="eager">
                </div>
            </aside>
        </main>

        <footer id="tvFooter" class="mt-4 rounded-xl border border-slate-800 bg-slate-900 p-4 hidden">
            <div id="tvFooterTitleContainer" class="tv-title-container">
                <h1 id="tvFooterTitle" class="tv-title-text text-2xl md:text-3xl font-semibold tracking-tight">Lista de Produtos (TV)</h1>
            </div>
        </footer>
    </div>

    <p id="statusMessage" class="tv-status-floating">Pronto para teste de tela cheia.</p>

    <div id="tvLeftVerticalLogoSlot" class="hidden is-placeholder">
        <img id="tvLeftVerticalLogo" class="hidden" alt="Logo vertical esquerda" loading="eager">
    </div>

    <button id="fullscreenTestButton" type="button" class="tv-fullscreen-test-btn" aria-label="Testar tela cheia" onclick="toggleTelaCheia(event)">
        •
    </button>

    <script>
        var fullscreenTestButton = document.getElementById('fullscreenTestButton');
        var bolinhaHideTimer = null;

        function isTelaCheiaAtiva() {
            return !!(
                document.fullscreenElement
                || document.webkitFullscreenElement
                || document.msFullscreenElement
            );
        }

        function isTypingTarget(element) {
            if (!element) {
                return false;
            }

            var tagName = String(element.tagName || '').toLowerCase();
            return element.isContentEditable || tagName === 'input' || tagName === 'textarea' || tagName === 'select';
        }

        function clearBolinhaHideTimer() {
            if (!bolinhaHideTimer) {
                return;
            }

            clearTimeout(bolinhaHideTimer);
            bolinhaHideTimer = null;
        }

        function hideBolinhaSoft() {
            if (!fullscreenTestButton) {
                return;
            }

            fullscreenTestButton.classList.remove('is-highlight');
            fullscreenTestButton.classList.add('is-hidden-soft');
        }

        function showBolinhaForExit(shouldFocus) {
            if (!fullscreenTestButton) {
                return;
            }

            fullscreenTestButton.classList.remove('is-hidden-soft');
            fullscreenTestButton.classList.add('is-highlight');
            clearBolinhaHideTimer();
            bolinhaHideTimer = setTimeout(hideBolinhaSoft, 4500);

            if (shouldFocus) {
                try {
                    fullscreenTestButton.focus({ preventScroll: true });
                } catch (_error) {
                    fullscreenTestButton.focus();
                }
            }
        }

        function abrirTelaCheia() {
            var el = document.documentElement;

            try {
                if (el.requestFullscreen) {
                    el.requestFullscreen();
                    return;
                }

                if (el.webkitRequestFullscreen) {
                    el.webkitRequestFullscreen();
                    return;
                }

                if (el.msRequestFullscreen) {
                    el.msRequestFullscreen();
                    return;
                }

                var video = document.getElementById('tvVideo');
                if (video && video.webkitEnterFullscreen) {
                    video.webkitEnterFullscreen();
                }
            } catch (_error) {
            }
        }

        function sairTelaCheia() {
            try {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    return;
                }

                if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                    return;
                }

                if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            } catch (_error) {
            }
        }

        function toggleTelaCheia(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            if (isTelaCheiaAtiva()) {
                sairTelaCheia();
                return;
            }

            abrirTelaCheia();
        }

        window.abrirTelaCheia = abrirTelaCheia;
        window.toggleTelaCheia = toggleTelaCheia;
        window.sairTelaCheia = sairTelaCheia;
        window.showBolinhaForExit = showBolinhaForExit;

        (function setupRightSidebarLogoFallback() {
            var slot = document.getElementById('tvRightSidebarLogoSlot');
            var image = document.getElementById('tvRightSidebarLogo');
            var leftSlot = document.getElementById('tvLeftVerticalLogoSlot');
            var leftImage = document.getElementById('tvLeftVerticalLogo');

            if (!slot && !leftSlot) {
                return;
            }

            if (slot) {
                slot.classList.add('hidden');
                slot.style.display = 'none';
            }

            if (leftSlot) {
                leftSlot.classList.add('hidden');
                leftSlot.style.display = 'none';
            }

            function getToken() {
                try {
                    var params = new URLSearchParams(window.location.search || '');
                    return params.get('token') || localStorage.getItem('tv_device_token') || '';
                } catch (_error) {
                    return '';
                }
            }

            function applySimpleImageSlot(targetSlot, targetImage, shouldShow, logoUrl, width, height) {
                if (!targetSlot) {
                    return;
                }

                targetSlot.style.width = width + 'px';
                targetSlot.style.height = height + 'px';
                targetSlot.style.minHeight = height + 'px';
                targetSlot.classList.toggle('hidden', !shouldShow);
                targetSlot.style.display = shouldShow ? '' : 'none';

                if (!targetImage) {
                    return;
                }

                targetImage.style.maxWidth = width + 'px';
                targetImage.style.maxHeight = Math.max(20, height - 10) + 'px';

                if (!shouldShow) {
                    targetSlot.classList.add('is-placeholder');
                    targetImage.classList.add('hidden');
                    targetImage.removeAttribute('src');
                    return;
                }

                var url = String(logoUrl || '').trim();
                if (!url) {
                    targetSlot.classList.add('is-placeholder');
                    targetImage.classList.add('hidden');
                    targetImage.removeAttribute('src');
                    return;
                }

                targetImage.onload = function () {
                    targetSlot.classList.remove('is-placeholder');
                    targetImage.classList.remove('hidden');
                };

                targetImage.onerror = function () {
                    targetSlot.classList.add('is-placeholder');
                    targetImage.classList.add('hidden');
                };

                targetImage.src = url;
            }

            function applyLogoState(data) {
                var showRightLogo = Boolean(data.showRightSidebarLogo) && Boolean(data.showRightSidebarPanel);
                var rightLogoUrl = data.rightSidebarLogoUrl || '';
                var rightLogoWidth = Math.max(60, Math.min(1200, Number(data.rightSidebarLogoWidth || 220)));
                var rightLogoHeight = Math.max(30, Math.min(300, Number(data.rightSidebarLogoHeight || 58)));
                var rightLogoBackgroundColor = String(data.rightSidebarLogoBackgroundColor || '#0f172a');
                var rightLogoBackgroundTransparent = Boolean(data.isRightSidebarLogoBackgroundTransparent);

                var showLeftLogo = Boolean(data.showLeftVerticalLogo);
                var leftLogoUrl = data.leftVerticalLogoUrl || '';
                var leftLogoWidth = Math.max(40, Math.min(1000, Number(data.leftVerticalLogoWidth || 120)));
                var leftLogoHeight = Math.max(40, Math.min(1000, Number(data.leftVerticalLogoHeight || 220)));

                if (slot) {
                    slot.style.background = rightLogoBackgroundTransparent ? 'transparent' : rightLogoBackgroundColor;
                }

                applySimpleImageSlot(slot, image, showRightLogo, rightLogoUrl, rightLogoWidth, rightLogoHeight);
                applySimpleImageSlot(leftSlot, leftImage, showLeftLogo, leftLogoUrl, leftLogoWidth, leftLogoHeight);
            }

            function hideAllLogoSlots() {
                if (slot) {
                    slot.classList.add('hidden');
                    slot.style.display = 'none';
                }

                if (leftSlot) {
                    leftSlot.classList.add('hidden');
                    leftSlot.style.display = 'none';
                }
            }

            var token = getToken();
            if (!token) {
                hideAllLogoSlots();
                return;
            }

            fetch('/api/tv/totemweb/config', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    Authorization: 'Bearer ' + token,
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (payload) {
                    var data = payload && payload.data ? payload.data : {};
                    applyLogoState(data);
                })
                .catch(function () {
                    hideAllLogoSlots();
                });
        })();

        document.addEventListener('fullscreenchange', function () {
            if (isTelaCheiaAtiva()) {
                showBolinhaForExit(false);
                return;
            }

            clearBolinhaHideTimer();
            if (fullscreenTestButton) {
                fullscreenTestButton.classList.remove('is-hidden-soft');
                fullscreenTestButton.classList.remove('is-highlight');
            }
        });

        document.addEventListener('webkitfullscreenchange', function () {
            if (isTelaCheiaAtiva()) {
                showBolinhaForExit(false);
                return;
            }

            clearBolinhaHideTimer();
            if (fullscreenTestButton) {
                fullscreenTestButton.classList.remove('is-hidden-soft');
                fullscreenTestButton.classList.remove('is-highlight');
            }
        });

        document.addEventListener('msfullscreenchange', function () {
            if (isTelaCheiaAtiva()) {
                showBolinhaForExit(false);
                return;
            }

            clearBolinhaHideTimer();
            if (fullscreenTestButton) {
                fullscreenTestButton.classList.remove('is-hidden-soft');
                fullscreenTestButton.classList.remove('is-highlight');
            }
        });

        document.addEventListener('click', function (event) {
            if (isTypingTarget(event.target)) {
                return;
            }

            if (!isTelaCheiaAtiva()) {
                abrirTelaCheia();
                return;
            }

            if (!fullscreenTestButton || event.target !== fullscreenTestButton) {
                showBolinhaForExit(false);
            }
        }, { passive: true });

        window.addEventListener('keydown', function (event) {
            var key = String(event.key || '').toLowerCase();
            var keyCode = Number(event.keyCode || event.which || 0);
            var isEnter = key === 'enter' || key === 'numpadenter' || keyCode === 13;

            if (!isEnter) {
                return;
            }

            if (isTypingTarget(event.target)) {
                return;
            }

            if (!isTelaCheiaAtiva()) {
                abrirTelaCheia();
                return;
            }

            if (document.activeElement === fullscreenTestButton) {
                return;
            }

            showBolinhaForExit(true);
        });

        if (isTelaCheiaAtiva()) {
            showBolinhaForExit(false);
        }
    </script>

</body>
</html>
