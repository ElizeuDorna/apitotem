<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TV Produtos</title>
    @vite(['resources/css/app.css', 'resources/js/tv-produtos.js'])
    <style>
        html, body {
            width: 100%;
            min-height: 100%;
        }

        body {
            font-size: clamp(14px, 1.1vw, 20px);
        }

        .tv-shell {
            width: 100%;
            min-height: 100vh;
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
            align-items: stretch;
            justify-content: center;
        }

        #tvVideo,
        #tvEmbed,
        #tvImageSlide {
            width: 100%;
            height: auto;
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
                <video id="tvVideo" class="w-full rounded-lg bg-black" controls autoplay playsinline>
                    <source src="/tv/videos/demo.mp4" type="video/mp4">
                    Seu navegador não suporta vídeo HTML5.
                </video>
                <iframe id="tvEmbed" class="hidden w-full rounded-lg bg-black" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                <img id="tvImageSlide" class="hidden w-full rounded-lg bg-black" alt="Slide lateral" loading="eager">
            </aside>
        </main>

        <footer id="tvFooter" class="mt-4 rounded-xl border border-slate-800 bg-slate-900 p-4 hidden">
            <div id="tvFooterTitleContainer" class="tv-title-container">
                <h1 id="tvFooterTitle" class="tv-title-text text-2xl md:text-3xl font-semibold tracking-tight">Lista de Produtos (TV)</h1>
            </div>
        </footer>
    </div>

</body>
</html>
