<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configuração da Tela Web 01
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

                    @if (session('embedWarnings'))
                        <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-900 mb-4 border border-amber-200">
                            <p class="font-semibold mb-1">Aviso de incorporação de vídeo (YouTube)</p>
                            <ul class="list-disc ml-5 space-y-1">
                                @foreach ((array) session('embedWarnings') as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-2 text-xs text-amber-800">Dica: se aparecer “vídeo indisponível” na TV, troque por outro link do YouTube com incorporação permitida.</p>
                        </div>
                    @endif

                    @php
                        $embedStatuses = session('embedStatuses', []);

                        $savedPlaylist = collect($config->videoPlaylist ?? []);
                        if ($savedPlaylist->isEmpty()) {
                            $savedPlaylist = collect(preg_split('/\r?\n|,|;\s*/', (string) ($config->videoUrl ?? '')))
                                ->map(fn ($value) => trim((string) $value))
                                ->filter()
                                ->values()
                                ->map(fn ($url) => ['url' => $url, 'muted' => (bool) ($config->videoMuted ?? false), 'active' => true]);
                        }

                        $savedPlaylist = collect(range(0, 9))->map(function ($index) use ($savedPlaylist) {
                            $item = $savedPlaylist->get($index, []);

                            return [
                                'url' => (string) ($item['url'] ?? ''),
                                'muted' => (bool) ($item['muted'] ?? false),
                                'active' => (bool) ($item['active'] ?? false),
                                'fullscreen' => (bool) ($item['fullscreen'] ?? false),
                                'durationSeconds' => (int) ($item['durationSeconds'] ?? 0),
                                'heightPx' => (int) ($item['heightPx'] ?? 0),
                            ];
                        });

                        $hasVideoValidationErrors = !empty($errors->get('video_urls.*'))
                            || $errors->has('videoUrl')
                            || $errors->has('videoPlaylist')
                            || $errors->has('videoBackgroundColor');
                    @endphp

                    <form method="POST" action="{{ route('admin.web-screen-config.update') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 items-start">
                            <aside id="configAccordionMenu" class="rounded-md border border-gray-200 bg-gray-50 p-3 space-y-2">
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="videoConfigSection">Configuração de Vídeos</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="colorConfigSection">Configuração de Cores</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="displayConfigSection">Exibição da Tela</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="rightSidebarConfigSection">Configuração Tela Lateral Direita</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="companyGalleryConfigSection">Galeria Imagem da Empresa</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="imageSizeConfigSection">Fonte do produto</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="paginationConfigSection">Paginação da Lista</button>
                            </aside>

                            <div id="configPanelsStorage" class="space-y-4 hidden">
                        <div id="videoConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3 hidden">
                            <h3 class="text-base font-semibold text-gray-800">Configuração de Vídeos</h3>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="showVideoPanel" value="0">
                                <input type="checkbox" name="showVideoPanel" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showVideoPanel', $config->showVideoPanel ?? true))>
                                <span class="text-sm text-gray-700">Ativar vídeos da lateral direita</span>
                            </label>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cor do fundo dos vídeos</label>
                                <input type="color" id="videoBackgroundColor" name="videoBackgroundColor" value="{{ old('videoBackgroundColor', $config->videoBackgroundColor ?? '#000000') }}" class="w-full h-10 border rounded">
                                @error('videoBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror

                                <label class="mt-2 inline-flex items-center gap-2">
                                    <input type="hidden" name="isVideoPanelTransparent" value="0">
                                    <input type="checkbox" id="isVideoPanelTransparent" name="isVideoPanelTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isVideoPanelTransparent', $config->isVideoPanelTransparent ?? false))>
                                    <span class="text-sm text-gray-700">Deixar fundo do vídeo transparente</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 gap-3">
                                @for ($index = 0; $index < 10; $index++)
                                    <div class="rounded-md border border-gray-300 bg-white p-3">
                                        @php
                                            $embedStatus = $embedStatuses[$index]['status'] ?? null;
                                            $embedMessage = $embedStatuses[$index]['message'] ?? null;
                                            $urlInputClass = 'w-full border rounded px-3 py-2';

                                            if ($embedStatus === 'ok') {
                                                $urlInputClass .= ' bg-green-50 border-green-300';
                                            } elseif ($embedStatus === 'likely') {
                                                $urlInputClass .= ' bg-amber-50 border-amber-300';
                                            } elseif ($embedStatus === 'blocked') {
                                                $urlInputClass .= ' bg-red-50 border-red-300';
                                            }
                                        @endphp

                                        <label class="block text-sm font-medium text-gray-700 mb-1">Vídeo {{ $index + 1 }}</label>
                                        <input
                                            type="text"
                                            name="video_urls[{{ $index }}]"
                                            value="{{ old('video_urls.' . $index, $savedPlaylist[$index]['url'] ?? '') }}"
                                            placeholder="https://... ou <iframe ...>"
                                            class="{{ $urlInputClass }}"
                                        >
                                        @error('video_urls.' . $index)<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        <p class="text-[11px] text-gray-500 mt-1">Aceita link direto ou código de incorporação (iframe). O sistema extrai automaticamente o <code>src</code>.</p>
                                        @if ($embedStatus === 'ok')
                                            <p class="text-xs text-green-700 mt-1">Link validado para incorporação.</p>
                                        @elseif ($embedStatus === 'likely')
                                            <p class="text-xs text-amber-700 mt-1">Sem bloqueio detectado no servidor, mas pode falhar em alguns dispositivos/regiões.</p>
                                        @elseif ($embedStatus === 'blocked')
                                            <p class="text-xs text-red-700 mt-1">{{ $embedMessage }}</p>
                                        @endif

                                        <label class="mt-2 inline-flex items-center gap-2">
                                            <input type="hidden" name="video_active_flags[{{ $index }}]" value="0">
                                            <input
                                                type="checkbox"
                                                name="video_active_flags[{{ $index }}]"
                                                value="1"
                                                class="rounded border-gray-300 text-indigo-600"
                                                @checked(old('video_active_flags.' . $index, ($savedPlaylist[$index]['url'] ?? '') !== '' ? ($savedPlaylist[$index]['active'] ?? false) : false))
                                            >
                                            <span class="text-xs text-gray-700">Ativar vídeo (se inativo, não aparece na tela)</span>
                                        </label>

                                        <label class="mt-2 inline-flex items-center gap-2">
                                            <input type="hidden" name="video_fullscreen_flags[{{ $index }}]" value="0">
                                            <input
                                                type="checkbox"
                                                name="video_fullscreen_flags[{{ $index }}]"
                                                value="1"
                                                class="rounded border-gray-300 text-indigo-600"
                                                @checked(old('video_fullscreen_flags.' . $index, ($savedPlaylist[$index]['url'] ?? '') !== '' ? ($savedPlaylist[$index]['fullscreen'] ?? false) : false))
                                            >
                                            <span class="text-xs text-gray-700">Abrir em tela cheia (somente este vídeo)</span>
                                        </label>

                                        <label class="mt-2 inline-flex items-center gap-2">
                                            <input type="hidden" name="video_muted_flags[{{ $index }}]" value="0">
                                            <input
                                                type="checkbox"
                                                name="video_muted_flags[{{ $index }}]"
                                                value="1"
                                                class="rounded border-gray-300 text-indigo-600"
                                                @checked(old('video_muted_flags.' . $index, $savedPlaylist[$index]['muted'] ?? false))
                                            >
                                            <span class="text-xs text-gray-600">Rodar sem áudio (somente este vídeo)</span>
                                        </label>

                                        <div class="mt-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Tempo fallback (segundos)</label>
                                            <input
                                                type="number"
                                                name="video_duration_seconds[{{ $index }}]"
                                                min="0"
                                                max="86400"
                                                value="{{ old('video_duration_seconds.' . $index, $savedPlaylist[$index]['durationSeconds'] ?? 0) }}"
                                                class="w-full border rounded px-3 py-2"
                                            >
                                            @error('video_duration_seconds.' . $index)<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                            <p class="mt-1 text-[11px] text-gray-500">Use para fontes sem detecção de fim (Drive/OneDrive/embeds). 0 = desativado.</p>
                                        </div>

                                        <div class="mt-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Altura do vídeo (px)</label>
                                            <input
                                                type="number"
                                                name="video_heights[{{ $index }}]"
                                                min="0"
                                                max="2000"
                                                value="{{ old('video_heights.' . $index, $savedPlaylist[$index]['heightPx'] ?? 0) }}"
                                                class="w-full border rounded px-3 py-2"
                                            >
                                            @error('video_heights.' . $index)<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                            <p class="mt-1 text-[11px] text-gray-500">0 = automático (padrão). Valor > 0 força altura somente neste vídeo.</p>
                                        </div>
                                    </div>
                                @endfor
                                <p class="text-xs text-gray-500">A reprodução na TV segue a ordem: Vídeo 1 até Vídeo 10.</p>
                            </div>
                        </div>

                        <div id="colorConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <h3 class="text-base font-semibold text-gray-800">Configuração de Cores</h3>
                            <p class="text-sm text-gray-600">Aqui ficam somente as cores da Tela Web 01.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor de toda a tela</label>
                                    <input type="color" name="appBackgroundColor" value="{{ old('appBackgroundColor', $config->appBackgroundColor ?? '#0f172a') }}" class="w-full h-10 border rounded">
                                    @error('appBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do fundo do painel de produtos</label>
                                    <input type="color" id="productsPanelBackgroundColor" name="productsPanelBackgroundColor" value="{{ old('productsPanelBackgroundColor', $config->productsPanelBackgroundColor ?? '#0f172a') }}" class="w-full h-10 border rounded">
                                    @error('productsPanelBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror

                                    <label class="mt-2 inline-flex items-center gap-2">
                                        <input type="hidden" name="isProductsPanelTransparent" value="0">
                                        <input type="checkbox" id="isProductsPanelTransparent" name="isProductsPanelTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isProductsPanelTransparent', $config->isProductsPanelTransparent ?? false))>
                                        <span class="text-sm text-gray-700">Deixar painel de produtos transparente</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor da borda da lista</label>
                                    <input type="color" id="listBorderColor" name="listBorderColor" value="{{ old('listBorderColor', $config->listBorderColor ?? '#334155') }}" class="w-full h-10 border rounded">
                                    @error('listBorderColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror

                                    <div class="mt-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Grossura da borda da lista (px)</label>
                                        <input type="number" id="listBorderWidth" name="listBorderWidth" min="0" max="20" value="{{ old('listBorderWidth', $config->listBorderWidth ?? 1) }}" class="w-full border rounded px-3 py-2">
                                        @error('listBorderWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>

                                    <label class="mt-2 inline-flex items-center gap-2">
                                        <input type="hidden" name="isListBorderTransparent" value="0">
                                        <input type="checkbox" id="isListBorderTransparent" name="isListBorderTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isListBorderTransparent', $config->isListBorderTransparent ?? false))>
                                        <span class="text-sm text-gray-700">Desativar borda da lista</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do fundo da linha</label>
                                    <input type="color" id="rowBackgroundColor" name="rowBackgroundColor" value="{{ old('rowBackgroundColor', $config->rowBackgroundColor ?? '#0b1220') }}" class="w-full h-10 border rounded">
                                    @error('rowBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor da borda da linha</label>
                                    <input type="color" id="borderColor" name="borderColor" value="{{ old('borderColor', $config->borderColor ?? '#334155') }}" class="w-full h-10 border rounded">
                                    @error('borderColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror

                                    <div class="mt-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Grossura da borda da linha (px)</label>
                                        <input type="number" id="rowBorderWidth" name="rowBorderWidth" min="0" max="20" value="{{ old('rowBorderWidth', $config->rowBorderWidth ?? 1) }}" class="w-full border rounded px-3 py-2">
                                        @error('rowBorderWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>

                                    <label class="mt-2 inline-flex items-center gap-2">
                                        <input type="hidden" name="isRowBorderTransparent" value="0">
                                        <input type="checkbox" id="isRowBorderTransparent" name="isRowBorderTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isRowBorderTransparent', $config->isRowBorderTransparent ?? false))>
                                        <span class="text-sm text-gray-700">Deixar borda da linha transparente</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do nome/preço do produto</label>
                                    <input type="color" name="priceColor" value="{{ old('priceColor', $config->priceColor ?? '#818cf8') }}" class="w-full h-10 border rounded">
                                    @error('priceColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div id="gradientFields" class="rounded-md border border-gray-200 bg-white p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor inicial do degradê</label>
                                    <input type="color" name="gradientStartColor" value="{{ old('gradientStartColor', $config->gradientStartColor ?? '#111827') }}" class="w-full h-10 border rounded">
                                    @error('gradientStartColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor final do degradê</label>
                                    <input type="color" name="gradientEndColor" value="{{ old('gradientEndColor', $config->gradientEndColor ?? '#1f2937') }}" class="w-full h-10 border rounded">
                                    @error('gradientEndColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="useGradient" value="0">
                                <input type="checkbox" id="useGradient" name="useGradient" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('useGradient', $config->useGradient))>
                                <span class="text-sm text-gray-700">Usar degradê na linha</span>
                            </label>
                        </div>

                        <div id="displayConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <h3 class="text-base font-semibold text-gray-800">Exibição da Tela</h3>
                            <p class="text-sm text-gray-600">Opções de layout e visibilidade dos elementos.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="showBorder" value="0">
                                <input type="checkbox" name="showBorder" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showBorder', $config->showBorder))>
                                <span class="text-sm text-gray-700">Mostrar borda da linha</span>
                            </label>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="showTitle" value="0">
                                <input type="checkbox" name="showTitle" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showTitle', $config->showTitle ?? true))>
                                <span class="text-sm text-gray-700">Mostrar título no topo da tela</span>
                            </label>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="showImage" value="0">
                                <input type="checkbox" name="showImage" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showImage', $config->showImage ?? true))>
                                <span class="text-sm text-gray-700">Mostrar imagem do produto na lista</span>
                            </label>
                            </div>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showBackgroundImage" value="0">
                                    <input type="checkbox" id="showBackgroundImage" name="showBackgroundImage" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showBackgroundImage', $config->showBackgroundImage ?? false))>
                                    <span class="text-sm text-gray-700">Ativar imagem de fundo da tela</span>
                                </label>

                                <div id="backgroundImageUrlField">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Caminho/URL da imagem de fundo</label>
                                    <input
                                        type="url"
                                        name="backgroundImageUrl"
                                        value="{{ old('backgroundImageUrl', $config->backgroundImageUrl ?? '') }}"
                                        placeholder="https://..."
                                        class="w-full border rounded px-3 py-2"
                                    >
                                    @error('backgroundImageUrl')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                        </div>

                        <div id="rightSidebarConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <h3 class="text-base font-semibold text-gray-800">Configuração Tela Lateral Direita</h3>
                            <p class="text-sm text-gray-600">Configurações genéricas da coluna direita da tela.</p>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Tipo de mídia</h4>
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showRightSidebarPanel" value="0">
                                    <input type="checkbox" id="showRightSidebarPanel" name="showRightSidebarPanel" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showRightSidebarPanel', $config->showRightSidebarPanel ?? true))>
                                    <span class="text-sm text-gray-700">Ativar lateral direita completa</span>
                                </label>
                                <p class="text-xs text-gray-600">Quando desativado, a lateral direita e ocultada e a lista de produtos ocupa toda a largura da tela.</p>

                                <label class="inline-flex items-center gap-2 mr-6">
                                    <input type="radio" id="rightSidebarMediaTypeVideo" name="rightSidebarMediaType" value="video" class="text-indigo-600 border-gray-300" @checked(old('rightSidebarMediaType', $config->rightSidebarMediaType ?? 'video') === 'video')>
                                    <span class="text-sm text-gray-700">Vídeo</span>
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" id="rightSidebarMediaTypeImage" name="rightSidebarMediaType" value="image" class="text-indigo-600 border-gray-300" @checked(old('rightSidebarMediaType', $config->rightSidebarMediaType ?? 'video') === 'image')>
                                    <span class="text-sm text-gray-700">Slide de imagens (links)</span>
                                </label>
                                <label class="inline-flex items-center gap-2 ml-0 md:ml-6">
                                    <input type="radio" id="rightSidebarMediaTypeHybrid" name="rightSidebarMediaType" value="hybrid" class="text-indigo-600 border-gray-300" @checked(old('rightSidebarMediaType', $config->rightSidebarMediaType ?? 'video') === 'hybrid')>
                                    <span class="text-sm text-gray-700">Híbrido (vídeo + slide)</span>
                                </label>
                                @error('rightSidebarMediaType')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                            </div>

                            <div id="rightSidebarHybridConfig" class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Tempo de alternância no modo híbrido</h4>
                                <p class="text-xs text-gray-600">Quando em híbrido, alterna por quantidade: N vídeos → M imagens → N vídeos, em loop.</p>
                                <p class="text-xs text-gray-600">A contagem de vídeos considera apenas os vídeos marcados como ativos em "Configuração de Vídeos".</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade de vídeos antes de ir para slide</label>
                                        <input type="number" id="rightSidebarHybridVideoDuration" name="rightSidebarHybridVideoDuration" min="1" max="1000" value="{{ old('rightSidebarHybridVideoDuration', $config->rightSidebarHybridVideoDuration ?? 2) }}" class="w-full border rounded px-3 py-2">
                                        @error('rightSidebarHybridVideoDuration')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade de imagens antes de voltar para vídeo</label>
                                        <input type="number" id="rightSidebarHybridImageDuration" name="rightSidebarHybridImageDuration" min="1" max="1000" value="{{ old('rightSidebarHybridImageDuration', $config->rightSidebarHybridImageDuration ?? 4) }}" class="w-full border rounded px-3 py-2">
                                        @error('rightSidebarHybridImageDuration')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor da borda</label>
                                        <input type="color" id="rightSidebarBorderColor" name="rightSidebarBorderColor" value="{{ old('rightSidebarBorderColor', $config->rightSidebarBorderColor ?? '#334155') }}" class="w-full h-10 border rounded">
                                        @error('rightSidebarBorderColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror

                                        <div class="mt-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Grossura da borda (px)</label>
                                            <input type="number" id="rightSidebarBorderWidth" name="rightSidebarBorderWidth" min="0" max="20" value="{{ old('rightSidebarBorderWidth', $config->rightSidebarBorderWidth ?? 1) }}" class="w-full border rounded px-3 py-2">
                                            @error('rightSidebarBorderWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho da imagem do slide (TV)</label>
                                        <p class="text-xs text-gray-600">Define o redimensionamento da imagem exibida no retangulo lateral direito da tela <code>/tv/telaweb01</code>.</p>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Altura (px)</label>
                                                <input type="number" id="rightSidebarImageHeight" name="rightSidebarImageHeight" min="0" max="1000" value="{{ old('rightSidebarImageHeight', $config->rightSidebarImageHeight ?? 96) }}" class="w-full border rounded px-3 py-2">
                                                @error('rightSidebarImageHeight')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Largura (px)</label>
                                                <input type="number" id="rightSidebarImageWidth" name="rightSidebarImageWidth" min="0" max="1000" value="{{ old('rightSidebarImageWidth', $config->rightSidebarImageWidth ?? 0) }}" class="w-full border rounded px-3 py-2">
                                                @error('rightSidebarImageWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                                <p class="text-xs text-gray-500 mt-1">Use 0 para altura/largura automatica.</p>
                                            </div>
                                        </div>
                                    </div>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showRightSidebarBorder" value="0">
                                    <input type="checkbox" id="showRightSidebarBorder" name="showRightSidebarBorder" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showRightSidebarBorder', $config->showRightSidebarBorder ?? true))>
                                    <span class="text-sm text-gray-700">Ativar borda da lateral direita</span>
                                </label>
                            </div>

                        </div>

                        <div id="companyGalleryConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <h3 class="text-base font-semibold text-gray-800">Galeria Imagem da Empresa</h3>
                            <p class="text-sm text-gray-600">Configurações da galeria de imagens da empresa para uso na lateral direita.</p>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código da Galeria Imagem Geral (opcional)</label>
                                <input
                                    type="text"
                                    id="rightSidebarGlobalGalleryCode"
                                    name="rightSidebarGlobalGalleryCode"
                                    inputmode="numeric"
                                    maxlength="14"
                                    value="{{ old('rightSidebarGlobalGalleryCode', $config->rightSidebarGlobalGalleryCode ?? '') }}"
                                    placeholder="Ex.: 78912345678901"
                                    class="w-full border rounded px-3 py-2"
                                >
                                @error('rightSidebarGlobalGalleryCode')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                <p class="text-xs text-gray-500">Se preencher, a empresa passa a usar as imagens da galeria global deste código.</p>

                                <div id="globalGalleryLookupFeedback" class="text-xs text-gray-500 mt-2">Digite o código para buscar imagens da galeria geral.</div>
                                <div id="globalGalleryProductStatus" class="text-xs text-gray-500 mt-1"></div>
                                <div id="globalGalleryLookupResults" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3"></div>
                                <div id="globalGalleryLookupEmpty" class="text-xs text-amber-700 mt-2 hidden">Nenhuma imagem encontrada para este código.</div>

                                <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3 space-y-2">
                                    <h4 class="text-sm font-semibold text-gray-800">Cadastro do Produto com mesmo código</h4>
                                    <p class="text-xs text-gray-600">Escolha qual imagem vai para o produto (somente uma). No slide você pode selecionar uma ou mais.</p>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="suggestedProductImageSource" value="none" class="rounded border-gray-300 text-indigo-600" @checked(old('suggestedProductImageSource', 'none') === 'none')>
                                        <span>Não alterar imagem do produto</span>
                                    </label>
                                    @error('suggestedProductImageSource')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror

                                    <div id="productSearchBlock" class="hidden mt-2 rounded-md border border-gray-200 bg-white p-3 space-y-2">
                                        <label class="block text-sm font-medium text-gray-700">Buscar produto (código ou descrição)</label>
                                        <input type="text" id="productSearchInput" placeholder="Digite código ou descrição" class="w-full border rounded px-3 py-2">
                                        <input type="hidden" name="selectedProductCode" id="selectedProductCode" value="{{ old('selectedProductCode', old('rightSidebarGlobalGalleryCode', $config->rightSidebarGlobalGalleryCode ?? '')) }}">
                                        @error('selectedProductCode')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        <div id="productSearchResults" class="space-y-1"></div>
                                        <div id="selectedProductBadge" class="text-xs text-green-700"></div>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-md border border-gray-200 bg-white p-3 space-y-3">
                                    <h4 class="text-sm font-semibold text-gray-800">Upload de Imagem Propria</h4>
                                    <p class="text-xs text-gray-600">Se não quiser usar sugestão da galeria geral, envie uma imagem própria da empresa.</p>
                                    <input type="file" name="companyGalleryUpload" id="companyGalleryUpload" accept="image/*" class="w-full border rounded px-3 py-2 bg-white">
                                    @error('companyGalleryUpload')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div class="mt-4 rounded-md border border-gray-200 bg-white p-3 space-y-3">
                                    <h4 class="text-sm font-semibold text-gray-800">Imagens já enviadas da empresa logada</h4>
                                    <p class="text-xs text-gray-600">Galeria da empresa (estilo biblioteca). Clique na imagem para abrir ações: usar no produto e/ou no slide.</p>

                                    @if (!empty($companyGalleryImages))
                                        <div class="max-h-[420px] overflow-y-auto pr-1 border border-gray-200 rounded-md p-2 bg-gray-50">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            @foreach ($companyGalleryImages as $index => $companyImage)
                                                @php
                                                    $sourceKey = 'company_existing_' . $index;
                                                    $isProductSelected = old('suggestedProductImageSource') === $sourceKey;
                                                    $isSlideSelected = in_array($sourceKey, (array) old('suggestedSlideImageSources', []), true);
                                                @endphp
                                                <div class="company-gallery-card rounded border border-gray-300 bg-gray-50 p-2 space-y-2" data-source-key="{{ $sourceKey }}">
                                                    <button type="button" class="w-full" data-company-gallery-preview="{{ $sourceKey }}">
                                                        <div class="w-full h-28 flex items-center justify-center bg-white rounded border border-transparent overflow-hidden">
                                                            <img src="{{ $companyImage['url'] }}" alt="Imagem da empresa" class="max-w-full max-h-full object-contain block mx-auto">
                                                        </div>
                                                    </button>
                                                    <p class="text-[11px] text-gray-600 truncate" title="{{ $companyImage['name'] }}">{{ $companyImage['name'] }}</p>

                                                    <div class="company-gallery-badge text-[11px] text-gray-500" data-company-gallery-badge="{{ $sourceKey }}">
                                                        @if($isProductSelected && $isSlideSelected)
                                                            Selecionada para produto e slide
                                                        @elseif($isProductSelected)
                                                            Selecionada para produto
                                                        @elseif($isSlideSelected)
                                                            Selecionada para slide
                                                        @else
                                                            Sem destino selecionado
                                                        @endif
                                                    </div>

                                                    <div class="company-gallery-actions hidden rounded border border-gray-200 bg-white p-2 space-y-2" data-company-gallery-actions="{{ $sourceKey }}">
                                                        <button type="button" class="w-full text-left text-xs rounded border border-gray-300 px-2 py-1 hover:bg-gray-50" data-company-mark-product="{{ $sourceKey }}">Usar no produto</button>
                                                        <button type="button" class="w-full text-left text-xs rounded border border-gray-300 px-2 py-1 hover:bg-gray-50" data-company-clear-product="{{ $sourceKey }}">Não usar no produto</button>
                                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                                            <input type="checkbox" name="suggestedSlideImageSources[]" value="{{ $sourceKey }}" class="company-slide-checkbox rounded border-gray-300 text-indigo-600" data-company-slide-checkbox="{{ $sourceKey }}" data-source-url="{{ $companyImage['url'] }}" @checked($isSlideSelected)>
                                                            <span>Usar no slide</span>
                                                        </label>
                                                    </div>

                                                    <input type="radio" name="suggestedProductImageSource" value="{{ $sourceKey }}" class="hidden company-product-radio" data-company-product-radio="{{ $sourceKey }}" @checked($isProductSelected)>
                                                </div>
                                            @endforeach
                                        </div>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-500">Nenhuma imagem da empresa encontrada ainda. Faça upload para começar.</p>
                                    @endif
                                </div>
                            </div>

                            <div id="rightSidebarImageConfig" class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Configuração do Slide de Imagens</h4>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Links das imagens (um por linha)</label>
                                    <textarea
                                        id="rightSidebarImageUrls"
                                        name="rightSidebarImageUrls"
                                        rows="5"
                                        placeholder="https://.../imagem1.jpg\nhttps://.../imagem2.png"
                                        class="w-full border rounded px-3 py-2"
                                    >{{ old('rightSidebarImageUrls', $config->rightSidebarImageUrls ?? '') }}</textarea>
                                    @error('rightSidebarImageUrls')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pré-visualização das imagens</label>
                                    <div id="rightSidebarImagePreview" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
                                    <p id="rightSidebarImagePreviewHint" class="mt-2 text-xs text-gray-500">Adicione links válidos para visualizar miniaturas.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempo por imagem (segundos)</label>
                                        <input type="number" id="rightSidebarImageInterval" name="rightSidebarImageInterval" min="1" max="300" value="{{ old('rightSidebarImageInterval', $config->rightSidebarImageInterval ?? 8) }}" class="w-full border rounded px-3 py-2">
                                        @error('rightSidebarImageInterval')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Ajuste da imagem</label>
                                        <select id="rightSidebarImageFit" name="rightSidebarImageFit" class="w-full border rounded px-3 py-2">
                                            <option value="contain" @selected(old('rightSidebarImageFit', $config->rightSidebarImageFit ?? 'scale-down') === 'contain')>Mostrar inteira (contain)</option>
                                            <option value="scale-down" @selected(old('rightSidebarImageFit', $config->rightSidebarImageFit ?? 'scale-down') === 'scale-down')>Mostrar inteira sem ampliar (recomendado)</option>
                                            <option value="cover" @selected(old('rightSidebarImageFit', $config->rightSidebarImageFit ?? 'scale-down') === 'cover')>Preencher área (cover)</option>
                                        </select>
                                        @error('rightSidebarImageFit')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="imageSizeConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <h3 class="text-base font-semibold text-gray-800">Fonte do produto</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Largura da imagem do produto (px)</label>
                                    <input type="number" name="imageWidth" min="20" max="400" value="{{ old('imageWidth', $config->imageWidth ?? 56) }}" class="w-full border rounded px-3 py-2">
                                    @error('imageWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Altura da imagem do produto (px)</label>
                                    <input type="number" name="imageHeight" min="20" max="400" value="{{ old('imageHeight', $config->imageHeight ?? 56) }}" class="w-full border rounded px-3 py-2">
                                    @error('imageHeight')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Espacamento vertical da linha (px)</label>
                                    <input type="number" name="rowVerticalPadding" min="0" max="40" value="{{ old('rowVerticalPadding', $config->rowVerticalPadding ?? 9) }}" class="w-full border rounded px-3 py-2">
                                    @error('rowVerticalPadding')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho da fonte da lista (px)</label>
                                    <input type="number" name="listFontSize" min="10" max="60" value="{{ old('listFontSize', $config->listFontSize ?? 16) }}" class="w-full border rounded px-3 py-2">
                                    @error('listFontSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho da fonte do grupo (topo) (px)</label>
                                    <input type="number" name="groupLabelFontSize" min="10" max="60" value="{{ old('groupLabelFontSize', $config->groupLabelFontSize ?? 14) }}" class="w-full border rounded px-3 py-2">
                                    @error('groupLabelFontSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do grupo (topo)</label>
                                    <input type="color" name="groupLabelColor" value="{{ old('groupLabelColor', $config->groupLabelColor ?? '#cbd5e1') }}" class="w-full h-10 border rounded">
                                    @error('groupLabelColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div id="paginationConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <h3 class="text-base font-semibold text-gray-800">Paginação da Lista</h3>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="isPaginationEnabled" value="0">
                                <input type="checkbox" id="isPaginationEnabled" name="isPaginationEnabled" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isPaginationEnabled', $config->isPaginationEnabled))>
                                <span class="text-sm text-gray-700">Ativar paginação automática</span>
                            </label>

                            <div id="paginationFields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Itens por página</label>
                                    <input type="number" name="pageSize" min="1" max="100" value="{{ old('pageSize', $config->pageSize ?? 10) }}" class="w-full border rounded px-3 py-2">
                                    @error('pageSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tempo de troca (segundos)</label>
                                    <input type="number" name="paginationInterval" min="1" max="120" value="{{ old('paginationInterval', $config->paginationInterval ?? 5) }}" class="w-full border rounded px-3 py-2">
                                    @error('paginationInterval')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>Salvar Configuração</x-primary-button>
                            <a href="{{ route('tv.telaweb01') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Abrir Tela Web 01</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const useGradient = document.getElementById('useGradient');
        const gradientFields = document.getElementById('gradientFields');
        const rowBackgroundColor = document.getElementById('rowBackgroundColor');
        const borderColor = document.getElementById('borderColor');
        const rowBorderWidth = document.getElementById('rowBorderWidth');
        const isRowBorderTransparent = document.getElementById('isRowBorderTransparent');
        const isPaginationEnabled = document.getElementById('isPaginationEnabled');
        const paginationFields = document.getElementById('paginationFields');
        const showBackgroundImage = document.getElementById('showBackgroundImage');
        const backgroundImageUrlField = document.getElementById('backgroundImageUrlField');
        const isProductsPanelTransparent = document.getElementById('isProductsPanelTransparent');
        const productsPanelBackgroundColor = document.getElementById('productsPanelBackgroundColor');
        const isVideoPanelTransparent = document.getElementById('isVideoPanelTransparent');
        const videoBackgroundColor = document.getElementById('videoBackgroundColor');
        const isListBorderTransparent = document.getElementById('isListBorderTransparent');
        const listBorderColor = document.getElementById('listBorderColor');
        const listBorderWidth = document.getElementById('listBorderWidth');
        const showRightSidebarBorder = document.getElementById('showRightSidebarBorder');
        const rightSidebarBorderColor = document.getElementById('rightSidebarBorderColor');
        const rightSidebarBorderWidth = document.getElementById('rightSidebarBorderWidth');
        const rightSidebarMediaTypeVideo = document.getElementById('rightSidebarMediaTypeVideo');
        const rightSidebarMediaTypeImage = document.getElementById('rightSidebarMediaTypeImage');
        const rightSidebarMediaTypeHybrid = document.getElementById('rightSidebarMediaTypeHybrid');
        const rightSidebarHybridConfig = document.getElementById('rightSidebarHybridConfig');
        const rightSidebarImageConfig = document.getElementById('rightSidebarImageConfig');
        const rightSidebarImageUrls = document.getElementById('rightSidebarImageUrls');
        const rightSidebarImagePreview = document.getElementById('rightSidebarImagePreview');
        const rightSidebarImagePreviewHint = document.getElementById('rightSidebarImagePreviewHint');
        const rightSidebarGlobalGalleryCode = document.getElementById('rightSidebarGlobalGalleryCode');
        const globalGalleryLookupFeedback = document.getElementById('globalGalleryLookupFeedback');
        const globalGalleryProductStatus = document.getElementById('globalGalleryProductStatus');
        const globalGalleryLookupResults = document.getElementById('globalGalleryLookupResults');
        const globalGalleryLookupEmpty = document.getElementById('globalGalleryLookupEmpty');
        const productSearchBlock = document.getElementById('productSearchBlock');
        const productSearchInput = document.getElementById('productSearchInput');
        const productSearchResults = document.getElementById('productSearchResults');
        const selectedProductCodeInput = document.getElementById('selectedProductCode');
        const selectedProductBadge = document.getElementById('selectedProductBadge');
        const companyGalleryCards = Array.from(document.querySelectorAll('.company-gallery-card'));
        const configAccordionMenu = document.getElementById('configAccordionMenu');
        const configPanelsStorage = document.getElementById('configPanelsStorage');
        const configMenuButtons = Array.from(document.querySelectorAll('.config-menu-btn'));
        const configPanels = Array.from(document.querySelectorAll('.config-panel'));
        let openedConfigPanelId = null;
        let globalGalleryLookupTimer = null;
        let productSearchTimer = null;
        let hasUserInteractedWithSlideSelection = false;

        function updateGradientVisibility() {
            if (!useGradient || !gradientFields) return;
            gradientFields.style.display = useGradient.checked ? 'grid' : 'none';

            if (rowBackgroundColor) {
                rowBackgroundColor.style.opacity = useGradient.checked ? '0.5' : '1';
                rowBackgroundColor.style.pointerEvents = useGradient.checked ? 'none' : 'auto';
            }
        }

        if (useGradient) {
            useGradient.addEventListener('change', updateGradientVisibility);
            updateGradientVisibility();
        }

        function updatePaginationVisibility() {
            if (!isPaginationEnabled || !paginationFields) return;
            paginationFields.style.display = isPaginationEnabled.checked ? 'grid' : 'none';
        }

        if (isPaginationEnabled) {
            isPaginationEnabled.addEventListener('change', updatePaginationVisibility);
            updatePaginationVisibility();
        }

        function updateBackgroundImageVisibility() {
            if (!showBackgroundImage || !backgroundImageUrlField) return;
            backgroundImageUrlField.style.display = showBackgroundImage.checked ? 'block' : 'none';
        }

        if (showBackgroundImage) {
            showBackgroundImage.addEventListener('change', updateBackgroundImageVisibility);
            updateBackgroundImageVisibility();
        }

        function updateProductsPanelBackgroundColorState() {
            if (!isProductsPanelTransparent || !productsPanelBackgroundColor) return;
            productsPanelBackgroundColor.style.opacity = isProductsPanelTransparent.checked ? '0.5' : '1';
            productsPanelBackgroundColor.style.pointerEvents = isProductsPanelTransparent.checked ? 'none' : 'auto';
        }

        if (isProductsPanelTransparent) {
            isProductsPanelTransparent.addEventListener('change', updateProductsPanelBackgroundColorState);
            updateProductsPanelBackgroundColorState();
        }

        function updateVideoBackgroundColorState() {
            if (!isVideoPanelTransparent || !videoBackgroundColor) return;
            videoBackgroundColor.style.opacity = isVideoPanelTransparent.checked ? '0.5' : '1';
            videoBackgroundColor.style.pointerEvents = isVideoPanelTransparent.checked ? 'none' : 'auto';
        }

        if (isVideoPanelTransparent) {
            isVideoPanelTransparent.addEventListener('change', updateVideoBackgroundColorState);
            updateVideoBackgroundColorState();
        }

        function updateBorderColorState() {
            if (!isRowBorderTransparent) return;
            if (borderColor) {
                borderColor.style.opacity = isRowBorderTransparent.checked ? '0.5' : '1';
                borderColor.style.pointerEvents = isRowBorderTransparent.checked ? 'none' : 'auto';
            }
            if (rowBorderWidth) {
                rowBorderWidth.style.opacity = isRowBorderTransparent.checked ? '0.5' : '1';
                rowBorderWidth.style.pointerEvents = isRowBorderTransparent.checked ? 'none' : 'auto';
            }
        }

        if (isRowBorderTransparent) {
            isRowBorderTransparent.addEventListener('change', updateBorderColorState);
            updateBorderColorState();
        }

        function updateListBorderColorState() {
            if (!isListBorderTransparent) return;
            if (listBorderColor) {
                listBorderColor.style.opacity = isListBorderTransparent.checked ? '0.5' : '1';
                listBorderColor.style.pointerEvents = isListBorderTransparent.checked ? 'none' : 'auto';
            }
            if (listBorderWidth) {
                listBorderWidth.style.opacity = isListBorderTransparent.checked ? '0.5' : '1';
                listBorderWidth.style.pointerEvents = isListBorderTransparent.checked ? 'none' : 'auto';
            }
        }

        if (isListBorderTransparent) {
            isListBorderTransparent.addEventListener('change', updateListBorderColorState);
            updateListBorderColorState();
        }

        function updateRightSidebarBorderState() {
            if (!showRightSidebarBorder) return;
            if (rightSidebarBorderColor) {
                rightSidebarBorderColor.style.opacity = showRightSidebarBorder.checked ? '1' : '0.5';
                rightSidebarBorderColor.style.pointerEvents = showRightSidebarBorder.checked ? 'auto' : 'none';
            }
            if (rightSidebarBorderWidth) {
                rightSidebarBorderWidth.style.opacity = showRightSidebarBorder.checked ? '1' : '0.5';
                rightSidebarBorderWidth.style.pointerEvents = showRightSidebarBorder.checked ? 'auto' : 'none';
            }
        }

        if (showRightSidebarBorder) {
            showRightSidebarBorder.addEventListener('change', updateRightSidebarBorderState);
            updateRightSidebarBorderState();
        }

        function updateRightSidebarMediaConfigState() {
            if (!rightSidebarImageConfig) return;
            const isImageMode = Boolean(rightSidebarMediaTypeImage && rightSidebarMediaTypeImage.checked);
            const isHybridMode = Boolean(rightSidebarMediaTypeHybrid && rightSidebarMediaTypeHybrid.checked);
            rightSidebarImageConfig.style.display = (isImageMode || isHybridMode) ? 'block' : 'none';

            if (rightSidebarHybridConfig) {
                rightSidebarHybridConfig.style.display = isHybridMode ? 'block' : 'none';
            }
        }

        function parseRightSidebarImageUrls() {
            if (!rightSidebarImageUrls) {
                return [];
            }

            return String(rightSidebarImageUrls.value || '')
                .split(/\r?\n|,|;\s*/)
                .map((value) => value.trim())
                .filter((value) => /^https?:\/\//i.test(value) || /^\/storage\//i.test(value) || /^storage\//i.test(value));
        }

        function renderRightSidebarImagePreview() {
            if (!rightSidebarImagePreview || !rightSidebarImagePreviewHint) {
                return;
            }

            const urls = parseRightSidebarImageUrls();
            rightSidebarImagePreview.innerHTML = '';

            if (urls.length === 0) {
                rightSidebarImagePreviewHint.textContent = 'Adicione links válidos para visualizar miniaturas.';
                return;
            }

            const maxPreviewItems = 20;
            const previewUrls = urls.slice(0, maxPreviewItems);

            previewUrls.forEach((url) => {
                const card = document.createElement('div');
                card.className = 'rounded border border-gray-300 bg-white overflow-hidden';

                const image = document.createElement('img');
                image.src = url;
                image.alt = 'Prévia da imagem';
                image.className = 'w-full h-24 object-cover';
                image.loading = 'lazy';
                image.onerror = () => {
                    image.classList.add('hidden');
                    const fallback = document.createElement('div');
                    fallback.className = 'h-24 flex items-center justify-center text-[11px] text-red-600 px-2 text-center';
                    fallback.textContent = 'Falha ao carregar imagem';
                    card.appendChild(fallback);
                };

                const caption = document.createElement('div');
                caption.className = 'px-2 py-1 text-[11px] text-gray-600 truncate';
                caption.textContent = url;
                caption.title = url;

                card.appendChild(image);
                card.appendChild(caption);
                rightSidebarImagePreview.appendChild(card);
            });

            if (urls.length > maxPreviewItems) {
                rightSidebarImagePreviewHint.textContent = `Mostrando ${maxPreviewItems} de ${urls.length} imagens.`;
            } else {
                rightSidebarImagePreviewHint.textContent = `${urls.length} imagem(ns) detectada(s).`;
            }
        }

        function normalizeSlideUrlForCompare(url) {
            const value = String(url || '').trim();
            if (value === '') {
                return '';
            }

            if (/^https?:\/\/localhost\/storage\//i.test(value)) {
                return value.replace(/^https?:\/\/localhost\/storage\//i, '/storage/');
            }

            if (/^storage\//i.test(value)) {
                return `/${value.replace(/^\/+/, '')}`;
            }

            return value;
        }

        function syncSelectedSlideUrlsToTextarea() {
            if (!rightSidebarImageUrls) {
                return;
            }

            if (!hasUserInteractedWithSlideSelection) {
                return;
            }

            const managedCheckboxes = Array.from(document.querySelectorAll('input[name="suggestedSlideImageSources[]"][data-source-url]'));

            const managedUrls = new Set(
                managedCheckboxes
                    .map((input) => normalizeSlideUrlForCompare(input.getAttribute('data-source-url')))
                    .filter((url) => url !== '')
            );

            const selectedUrls = managedCheckboxes
                .filter((input) => input.checked)
                .map((input) => normalizeSlideUrlForCompare(input.getAttribute('data-source-url')))
                .filter((url) => url !== '');

            const manualUrls = String(rightSidebarImageUrls.value || '')
                .split(/\r?\n/)
                .map((line) => normalizeSlideUrlForCompare(line))
                .filter((line) => line !== '' && !managedUrls.has(line));

            const finalUrls = Array.from(new Set([...manualUrls, ...selectedUrls]));
            rightSidebarImageUrls.value = finalUrls.join('\n');
            renderRightSidebarImagePreview();
        }

        if (rightSidebarMediaTypeVideo) {
            rightSidebarMediaTypeVideo.addEventListener('change', updateRightSidebarMediaConfigState);
        }

        if (rightSidebarMediaTypeImage) {
            rightSidebarMediaTypeImage.addEventListener('change', updateRightSidebarMediaConfigState);
        }

        if (rightSidebarMediaTypeHybrid) {
            rightSidebarMediaTypeHybrid.addEventListener('change', updateRightSidebarMediaConfigState);
        }

        if (rightSidebarImageUrls) {
            rightSidebarImageUrls.addEventListener('input', renderRightSidebarImagePreview);
        }

        updateRightSidebarMediaConfigState();
        renderRightSidebarImagePreview();

        function getOldSuggestedProductSource() {
            return @json(old('suggestedProductImageSource', 'none'));
        }

        function getOldSuggestedSlideSources() {
            return @json(array_values((array) old('suggestedSlideImageSources', [])));
        }

        function renderGlobalGalleryLookupResult(payload) {
            if (!globalGalleryLookupResults || !globalGalleryLookupFeedback || !globalGalleryLookupEmpty) {
                return;
            }

            globalGalleryLookupResults.innerHTML = '';
            globalGalleryLookupEmpty.classList.add('hidden');

            if (!payload?.found) {
                globalGalleryLookupFeedback.textContent = 'Código não encontrado na galeria geral.';
                globalGalleryLookupEmpty.classList.remove('hidden');

                if (globalGalleryProductStatus) {
                    if (payload?.productFound) {
                        globalGalleryProductStatus.className = 'text-xs text-green-700 mt-1';
                        globalGalleryProductStatus.textContent = `Produto encontrado na empresa: ${payload?.productName || 'Sem nome'}.`;
                    } else {
                        globalGalleryProductStatus.className = 'text-xs text-amber-700 mt-1';
                        globalGalleryProductStatus.textContent = 'Produto com este código não encontrado na empresa logada.';
                    }
                }

                updateProductSearchVisibility();
                return;
            }

            const oldProductSource = getOldSuggestedProductSource();
            const oldSlideSources = new Set(getOldSuggestedSlideSources());

            globalGalleryLookupFeedback.textContent = `Código ${payload.code} encontrado: ${payload.name}.`;

            if (globalGalleryProductStatus) {
                if (payload?.productFound) {
                    globalGalleryProductStatus.className = 'text-xs text-green-700 mt-1';
                    globalGalleryProductStatus.textContent = `Produto encontrado na empresa: ${payload?.productName || 'Sem nome'}.`;
                } else {
                    globalGalleryProductStatus.className = 'text-xs text-amber-700 mt-1';
                    globalGalleryProductStatus.textContent = 'Produto com este código não encontrado na empresa logada.';
                }
            }

            (payload.images || []).forEach((item) => {
                const slot = Number(item.slot || 0);
                const slotKey = String(item.slotKey || `slot_${slot}`);
                const url = String(item.url || '');

                if (!url) {
                    return;
                }

                const card = document.createElement('div');
                card.className = 'rounded border border-gray-300 bg-white p-2 space-y-2';

                const image = document.createElement('img');
                image.src = url;
                image.alt = `Imagem ${slot}`;
                image.className = 'w-full h-28 object-cover rounded';
                card.appendChild(image);

                const legend = document.createElement('p');
                legend.className = 'text-xs text-gray-600 truncate';
                legend.textContent = `Slot ${slot}`;
                legend.title = url;
                card.appendChild(legend);

                const productLabel = document.createElement('label');
                productLabel.className = 'inline-flex items-center gap-2 text-xs text-gray-700';
                productLabel.innerHTML = `<input type="radio" name="suggestedProductImageSource" value="${slotKey}" class="rounded border-gray-300 text-indigo-600" ${oldProductSource === slotKey ? 'checked' : ''}><span>Usar no produto</span>`;
                card.appendChild(productLabel);

                const slideLabel = document.createElement('label');
                slideLabel.className = 'inline-flex items-center gap-2 text-xs text-gray-700';
                slideLabel.innerHTML = `<input type="checkbox" name="suggestedSlideImageSources[]" value="${slotKey}" data-source-url="${url}" class="rounded border-gray-300 text-indigo-600" ${oldSlideSources.has(slotKey) ? 'checked' : ''}><span>Usar no slide</span>`;
                card.appendChild(slideLabel);

                globalGalleryLookupResults.appendChild(card);
            });

            if (!globalGalleryLookupResults.children.length) {
                globalGalleryLookupEmpty.classList.remove('hidden');
                globalGalleryLookupFeedback.textContent = 'Código encontrado, mas sem imagens válidas.';
            }

            updateProductSearchVisibility();
        }

        async function lookupGlobalGalleryByCode() {
            if (!rightSidebarGlobalGalleryCode) {
                return;
            }

            const code = String(rightSidebarGlobalGalleryCode.value || '').replace(/\D/g, '').slice(0, 14);

            if (!globalGalleryLookupFeedback || !globalGalleryLookupResults || !globalGalleryLookupEmpty) {
                return;
            }

            if (!code) {
                globalGalleryLookupResults.innerHTML = '';
                globalGalleryLookupEmpty.classList.add('hidden');
                globalGalleryLookupFeedback.textContent = 'Digite o código para buscar imagens da galeria geral.';
                if (globalGalleryProductStatus) {
                    globalGalleryProductStatus.className = 'text-xs text-gray-500 mt-1';
                    globalGalleryProductStatus.textContent = '';
                }
                return;
            }

            globalGalleryLookupFeedback.textContent = 'Buscando imagens...';
            globalGalleryLookupEmpty.classList.add('hidden');

            try {
                const response = await fetch(`{{ url('/admin/global-image-galleries/lookup') }}/${code}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                const payload = await response.json();
                renderGlobalGalleryLookupResult(payload);
            } catch (_error) {
                globalGalleryLookupFeedback.textContent = 'Não foi possível consultar a galeria agora.';
            }
        }

        if (rightSidebarGlobalGalleryCode) {
            rightSidebarGlobalGalleryCode.addEventListener('input', () => {
                rightSidebarGlobalGalleryCode.value = String(rightSidebarGlobalGalleryCode.value || '').replace(/\D/g, '').slice(0, 14);

                if (globalGalleryLookupTimer) {
                    clearTimeout(globalGalleryLookupTimer);
                }

                globalGalleryLookupTimer = setTimeout(() => {
                    lookupGlobalGalleryByCode();
                }, 350);
            });

            lookupGlobalGalleryByCode();
        }

        function hasSelectedProductImageSource() {
            const checked = document.querySelector('input[name="suggestedProductImageSource"]:checked');
            if (!checked) {
                return false;
            }

            return String(checked.value || 'none') !== 'none';
        }

        function updateProductSearchVisibility() {
            if (!productSearchBlock) {
                return;
            }

            productSearchBlock.classList.toggle('hidden', !hasSelectedProductImageSource());
        }

        function updateCompanyGalleryCardStates() {
            companyGalleryCards.forEach((card) => {
                const sourceKey = String(card.getAttribute('data-source-key') || '');
                if (!sourceKey) {
                    return;
                }

                const badge = card.querySelector(`[data-company-gallery-badge="${sourceKey}"]`);
                const productRadio = card.querySelector(`[data-company-product-radio="${sourceKey}"]`);
                const slideCheckbox = card.querySelector(`[data-company-slide-checkbox="${sourceKey}"]`);
                const image = card.querySelector('img');

                const isProductSelected = Boolean(productRadio && productRadio.checked);
                const isSlideSelected = Boolean(slideCheckbox && slideCheckbox.checked);

                card.classList.toggle('border-indigo-500', isProductSelected || isSlideSelected);
                card.classList.toggle('bg-indigo-50', isProductSelected || isSlideSelected);

                if (image) {
                    image.classList.toggle('ring-2', isProductSelected || isSlideSelected);
                    image.classList.toggle('ring-indigo-500', isProductSelected || isSlideSelected);
                }

                if (badge) {
                    if (isProductSelected && isSlideSelected) {
                        badge.textContent = 'Selecionada para produto e slide';
                        badge.className = 'company-gallery-badge text-[11px] text-indigo-700';
                    } else if (isProductSelected) {
                        badge.textContent = 'Selecionada para produto';
                        badge.className = 'company-gallery-badge text-[11px] text-indigo-700';
                    } else if (isSlideSelected) {
                        badge.textContent = 'Selecionada para slide';
                        badge.className = 'company-gallery-badge text-[11px] text-indigo-700';
                    } else {
                        badge.textContent = 'Sem destino selecionado';
                        badge.className = 'company-gallery-badge text-[11px] text-gray-500';
                    }
                }
            });
        }

        function openCompanyGalleryActions(sourceKey) {
            companyGalleryCards.forEach((card) => {
                const cardKey = String(card.getAttribute('data-source-key') || '');
                const actions = card.querySelector(`[data-company-gallery-actions="${cardKey}"]`);
                if (!actions) {
                    return;
                }

                if (cardKey === sourceKey) {
                    actions.classList.toggle('hidden');
                } else {
                    actions.classList.add('hidden');
                }
            });
        }

        function renderProductSearchResults(items) {
            if (!productSearchResults) {
                return;
            }

            productSearchResults.innerHTML = '';

            if (!Array.isArray(items) || items.length === 0) {
                productSearchResults.innerHTML = '<p class="text-xs text-gray-500">Nenhum produto encontrado.</p>';
                return;
            }

            items.forEach((item) => {
                const codigo = String(item?.codigo || '');
                const nome = String(item?.nome || 'Sem nome');

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left rounded border border-gray-200 px-3 py-2 hover:bg-gray-50';
                button.innerHTML = `<span class="text-xs font-semibold text-gray-700">${codigo}</span> <span class="text-xs text-gray-600">- ${nome}</span>`;
                button.addEventListener('click', () => {
                    if (selectedProductCodeInput) {
                        selectedProductCodeInput.value = codigo;
                    }

                    if (selectedProductBadge) {
                        selectedProductBadge.textContent = `Produto selecionado: ${codigo} - ${nome}`;
                    }
                });

                productSearchResults.appendChild(button);
            });
        }

        async function searchProductsForAssignment() {
            if (!productSearchInput || !productSearchResults) {
                return;
            }

            const query = String(productSearchInput.value || '').trim();
            if (query.length < 2) {
                productSearchResults.innerHTML = '<p class="text-xs text-gray-500">Digite ao menos 2 caracteres para buscar.</p>';
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.web-screen-config.search-products') }}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                const payload = await response.json();
                renderProductSearchResults(payload?.items || []);
            } catch (_error) {
                productSearchResults.innerHTML = '<p class="text-xs text-red-600">Falha ao buscar produtos.</p>';
            }
        }

        document.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            if (target.name === 'suggestedProductImageSource') {
                updateProductSearchVisibility();
                updateCompanyGalleryCardStates();
            }

            if (target.name === 'suggestedSlideImageSources[]' && target.hasAttribute('data-company-slide-checkbox')) {
                updateCompanyGalleryCardStates();
            }

            if (target.name === 'suggestedSlideImageSources[]') {
                hasUserInteractedWithSlideSelection = true;
                syncSelectedSlideUrlsToTextarea();
            }
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const previewButton = target.closest('[data-company-gallery-preview]');
            if (previewButton) {
                const sourceKey = String(previewButton.getAttribute('data-company-gallery-preview') || '');
                if (sourceKey) {
                    openCompanyGalleryActions(sourceKey);
                }
                return;
            }

            const markProductButton = target.closest('[data-company-mark-product]');
            if (markProductButton) {
                const sourceKey = String(markProductButton.getAttribute('data-company-mark-product') || '');
                const radio = document.querySelector(`[data-company-product-radio="${sourceKey}"]`);
                if (radio instanceof HTMLInputElement) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
                return;
            }

            const clearProductButton = target.closest('[data-company-clear-product]');
            if (clearProductButton) {
                const noneRadio = document.querySelector('input[name="suggestedProductImageSource"][value="none"]');
                if (noneRadio instanceof HTMLInputElement) {
                    noneRadio.checked = true;
                    noneRadio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });

        if (productSearchInput) {
            productSearchInput.addEventListener('input', () => {
                if (productSearchTimer) {
                    clearTimeout(productSearchTimer);
                }

                productSearchTimer = setTimeout(() => {
                    searchProductsForAssignment();
                }, 300);
            });
        }

        if (selectedProductBadge && selectedProductCodeInput && selectedProductCodeInput.value) {
            selectedProductBadge.textContent = `Produto selecionado: ${selectedProductCodeInput.value}`;
        }

        updateProductSearchVisibility();
        updateCompanyGalleryCardStates();

        function setConfigMenuButtonState(button, isActive) {
            if (!button) return;

            button.classList.toggle('bg-indigo-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-indigo-600', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
            button.classList.toggle('border-gray-300', !isActive);
        }

        function closeAllConfigPanels() {
            configPanels.forEach((panel) => {
                panel.classList.add('hidden');
                if (configPanelsStorage && panel.parentElement !== configPanelsStorage) {
                    configPanelsStorage.appendChild(panel);
                }
            });

            configMenuButtons.forEach((button) => setConfigMenuButtonState(button, false));
        }

        function openConfigPanel(targetId) {
            const targetPanel = configPanels.find((panel) => panel.id === targetId);
            const targetButton = configMenuButtons.find((button) => button.getAttribute('data-target') === targetId);

            if (!targetPanel || !targetButton || !configAccordionMenu) {
                return;
            }

            const isSamePanelOpen = openedConfigPanelId === targetId && !targetPanel.classList.contains('hidden');

            closeAllConfigPanels();

            if (isSamePanelOpen) {
                openedConfigPanelId = null;
                return;
            }

            targetButton.insertAdjacentElement('afterend', targetPanel);
            targetPanel.classList.remove('hidden');
            setConfigMenuButtonState(targetButton, true);
            openedConfigPanelId = targetId;
        }

        configMenuButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                if (targetId) {
                    openConfigPanel(targetId);
                }
            });
        });

        configMenuButtons.forEach((button) => setConfigMenuButtonState(button, false));

        const initialTarget = @json($hasVideoValidationErrors ? 'videoConfigSection' : null);
        if (initialTarget) {
            openConfigPanel(initialTarget);
        }
    </script>
</x-app-layout>
