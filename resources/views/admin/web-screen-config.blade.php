<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configuração da Totem Web
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

                        $normalizeSlideUrl = function ($value) {
                            $url = trim((string) $value);
                            if ($url === '') {
                                return '';
                            }

                            if (preg_match('#^https?://localhost/storage/(.+)$#i', $url, $matches) === 1) {
                                return '/storage/' . ltrim((string) ($matches[1] ?? ''), '/');
                            }

                            if (str_starts_with($url, 'storage/')) {
                                return '/' . ltrim($url, '/');
                            }

                            return $url;
                        };

                        $savedSlideUrls = collect(preg_split('/\r?\n/', (string) ($config->rightSidebarImageUrls ?? '')) ?: [])
                            ->map(fn ($line) => $normalizeSlideUrl($line))
                            ->filter(fn ($line) => $line !== '')
                            ->values();

                        $oldSlideSources = old('suggestedSlideImageSources');
                        $hasOldSlideSources = is_array($oldSlideSources);
                    @endphp

                    <form id="webConfigForm" method="POST" action="{{ route('admin.web-screen-config.update') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf
                        <input type="hidden" id="saveSection" name="saveSection" value="">

                        <div class="grid grid-cols-1 gap-4 items-start">
                            <aside id="configAccordionMenu" class="rounded-md border border-gray-200 bg-gray-50 p-3 space-y-2">
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="generalConfigSection">Configuração geral</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="videoConfigSection">Configuração de Vídeos</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="colorConfigSection">Configuração de Cores</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="rightSidebarConfigSection">Configuração Tela Lateral Direita</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="companyGalleryConfigSection">Galeria Imagem da Empresa</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="imageSizeConfigSection">Configuracao da lista produto</button>
                                <button type="button" class="config-menu-btn w-full text-left rounded-md border px-3 py-2 text-sm font-medium" data-target="paginationConfigSection">Paginação da Lista</button>
                            </aside>

                            <div id="configPanelsStorage" class="space-y-4 hidden">
                        <div id="generalConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Configuração geral</h3>
                                <button type="button" data-save-section="generalConfigSection" class="rounded-md border border-indigo-600 bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Salvar este menu</button>
                            </div>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Borda geral</h4>
                                <p class="text-xs text-gray-600">Aplica uma borda em toda a tela <code>/tv/totemweb</code>.</p>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="isMainBorderEnabled" value="0">
                                    <input type="checkbox" id="isMainBorderEnabled" name="isMainBorderEnabled" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isMainBorderEnabled', $config->isMainBorderEnabled ?? false))>
                                    <span class="text-sm text-gray-700">Ativar borda geral</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="isRoundedCornersEnabled" value="0">
                                    <input type="checkbox" id="isRoundedCornersEnabled" name="isRoundedCornersEnabled" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isRoundedCornersEnabled', $config->isRoundedCornersEnabled ?? true))>
                                    <span class="text-sm text-gray-700">Ativar cantos arredondados</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showRightSidebarLogo" value="0">
                                    <input type="checkbox" id="showRightSidebarLogo" name="showRightSidebarLogo" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showRightSidebarLogo', $config->showRightSidebarLogo ?? false))>
                                    <span class="text-sm text-gray-700">Mostrar logo no topo do retangulo direito</span>
                                </label>

                                <div>
                                    <label for="rightSidebarLogoPosition" class="block text-sm font-medium text-gray-700 mb-1">Local de exibicao da logo</label>
                                    <input type="hidden" id="rightSidebarLogoPosition" name="rightSidebarLogoPosition" value="sidebar_top">
                                    <div class="w-full border rounded px-3 py-2 text-sm bg-gray-100 text-gray-700">Lateral direita (no retangulo)</div>
                                    @error('rightSidebarLogoPosition')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div class="rounded-md border border-gray-200 bg-gray-50 p-3 space-y-3">
                                    <label for="rightSidebarLogoUpload" class="block text-sm font-medium text-gray-700">Logo personalizada da TV (opcional)</label>
                                    <input type="hidden" name="rightSidebarLogoUrl" value="{{ old('rightSidebarLogoUrl', $config->rightSidebarLogoUrl ?? '') }}">
                                    <input id="rightSidebarLogoUpload" name="rightSidebarLogoUpload" type="file" accept="image/png,image/jpeg,image/webp" class="w-full border rounded px-3 py-2 text-sm bg-white">
                                    <p class="text-xs text-gray-500">Se enviar uma imagem aqui, ela sera usada no topo do retangulo direito da TV.</p>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label for="rightSidebarLogoWidth" class="block text-sm font-medium text-gray-700 mb-1">Largura da logo (px)</label>
                                            <input id="rightSidebarLogoWidth" name="rightSidebarLogoWidth" type="number" min="60" max="1200" value="{{ old('rightSidebarLogoWidth', $config->rightSidebarLogoWidth ?? 220) }}" class="w-full border rounded px-3 py-2 text-sm bg-white">
                                            @error('rightSidebarLogoWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <label for="rightSidebarLogoHeight" class="block text-sm font-medium text-gray-700 mb-1">Altura da logo (px)</label>
                                            <input id="rightSidebarLogoHeight" name="rightSidebarLogoHeight" type="number" min="30" max="300" value="{{ old('rightSidebarLogoHeight', $config->rightSidebarLogoHeight ?? 58) }}" class="w-full border rounded px-3 py-2 text-sm bg-white">
                                            @error('rightSidebarLogoHeight')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label for="rightSidebarLogoBackgroundColor" class="block text-sm font-medium text-gray-700 mb-1">Cor da tarja da logo</label>
                                            <input id="rightSidebarLogoBackgroundColor" name="rightSidebarLogoBackgroundColor" type="color" value="{{ old('rightSidebarLogoBackgroundColor', $config->rightSidebarLogoBackgroundColor ?? '#0f172a') }}" class="w-full h-10 border rounded bg-white">
                                            @error('rightSidebarLogoBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>

                                        <div class="flex items-end">
                                            <label class="inline-flex items-center gap-2">
                                                <input type="hidden" name="isRightSidebarLogoBackgroundTransparent" value="0">
                                                <input type="checkbox" id="isRightSidebarLogoBackgroundTransparent" name="isRightSidebarLogoBackgroundTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isRightSidebarLogoBackgroundTransparent', $config->isRightSidebarLogoBackgroundTransparent ?? false))>
                                                <span class="text-sm text-gray-700">Deixar tarja transparente</span>
                                            </label>
                                        </div>
                                    </div>

                                    @php
                                        $logoPreviewUrl = old('rightSidebarLogoUrl', $config->rightSidebarLogoUrl ?? '');
                                    @endphp
                                    @if(!empty($logoPreviewUrl))
                                        <div class="rounded border border-gray-200 bg-white p-2 inline-flex items-center">
                                            <img src="{{ $logoPreviewUrl }}" alt="Preview da logo da TV" class="h-10 w-auto object-contain">
                                        </div>
                                    @endif

                                    @error('rightSidebarLogoUpload')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div class="rounded-md border border-gray-200 bg-gray-50 p-3 space-y-3">
                                    <h5 class="text-sm font-semibold text-gray-800">Logo vertical esquerda</h5>

                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="showLeftVerticalLogo" value="0">
                                        <input type="checkbox" id="showLeftVerticalLogo" name="showLeftVerticalLogo" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showLeftVerticalLogo', $config->showLeftVerticalLogo ?? false))>
                                        <span class="text-sm text-gray-700">Mostrar imagem na lateral esquerda da tela</span>
                                    </label>

                                    <label for="leftVerticalLogoUpload" class="block text-sm font-medium text-gray-700">Imagem da logo vertical esquerda (opcional)</label>
                                    <input type="hidden" name="leftVerticalLogoUrl" value="{{ old('leftVerticalLogoUrl', $config->leftVerticalLogoUrl ?? '') }}">
                                    <input id="leftVerticalLogoUpload" name="leftVerticalLogoUpload" type="file" accept="image/png,image/jpeg,image/webp" class="w-full border rounded px-3 py-2 text-sm bg-white">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label for="leftVerticalLogoWidth" class="block text-sm font-medium text-gray-700 mb-1">Largura da logo vertical esquerda (px)</label>
                                            <input id="leftVerticalLogoWidth" name="leftVerticalLogoWidth" type="number" min="40" max="1000" value="{{ old('leftVerticalLogoWidth', $config->leftVerticalLogoWidth ?? 120) }}" class="w-full border rounded px-3 py-2 text-sm bg-white">
                                            @error('leftVerticalLogoWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <label for="leftVerticalLogoHeight" class="block text-sm font-medium text-gray-700 mb-1">Altura da logo vertical esquerda (px)</label>
                                            <input id="leftVerticalLogoHeight" name="leftVerticalLogoHeight" type="number" min="40" max="1000" value="{{ old('leftVerticalLogoHeight', $config->leftVerticalLogoHeight ?? 220) }}" class="w-full border rounded px-3 py-2 text-sm bg-white">
                                            @error('leftVerticalLogoHeight')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    @php
                                        $leftVerticalLogoPreviewUrl = old('leftVerticalLogoUrl', $config->leftVerticalLogoUrl ?? '');
                                    @endphp
                                    @if(!empty($leftVerticalLogoPreviewUrl))
                                        <div class="rounded border border-gray-200 bg-white p-2 inline-flex items-center">
                                            <img src="{{ $leftVerticalLogoPreviewUrl }}" alt="Preview da logo vertical esquerda" class="h-10 w-auto object-contain">
                                        </div>
                                    @endif

                                    @error('leftVerticalLogoUpload')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Cor da borda geral</label>
                                        <input type="color" id="mainBorderColor" name="mainBorderColor" value="{{ old('mainBorderColor', $config->mainBorderColor ?? '#000000') }}" class="w-full h-10 border rounded">
                                        @error('mainBorderColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Grossura da borda geral (px)</label>
                                        <input type="number" id="mainBorderWidth" name="mainBorderWidth" min="0" max="40" value="{{ old('mainBorderWidth', $config->mainBorderWidth ?? 1) }}" class="w-full border rounded px-3 py-2">
                                        @error('mainBorderWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Título</h4>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showTitle" value="0">
                                    <input type="checkbox" name="showTitle" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showTitle', $config->showTitle ?? true))>
                                    <span class="text-sm text-gray-700">Mostrar título</span>
                                </label>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Texto do título</label>
                                    <input
                                        type="text"
                                        id="titleText"
                                        name="titleText"
                                        maxlength="120"
                                        value="{{ old('titleText', $config->titleText ?? 'Lista de Produtos (TV)') }}"
                                        placeholder="Digite o texto do título"
                                        class="w-full border rounded px-3 py-2"
                                    >
                                    @error('titleText')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="isTitleDynamic" value="0">
                                    <input type="checkbox" id="isTitleDynamic" name="isTitleDynamic" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isTitleDynamic', $config->isTitleDynamic ?? false))>
                                    <span class="text-sm text-gray-700">Ativar título dinâmico (direita para esquerda)</span>
                                </label>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posição do título</label>
                                    <select id="titlePosition" name="titlePosition" class="w-full border rounded px-3 py-2">
                                        <option value="top" @selected(old('titlePosition', $config->titlePosition ?? 'top') === 'top')>Mostrar no topo</option>
                                        <option value="footer" @selected(old('titlePosition', $config->titlePosition ?? 'top') === 'footer')>Mostrar no rodapé</option>
                                    </select>
                                    @error('titlePosition')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho da fonte do título (px)</label>
                                    <input type="number" id="titleFontSize" name="titleFontSize" min="10" max="96" value="{{ old('titleFontSize', $config->titleFontSize ?? 32) }}" class="w-full border rounded px-3 py-2">
                                    @error('titleFontSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Família da fonte do título</label>
                                    <select id="titleFontFamily" name="titleFontFamily" class="w-full border rounded px-3 py-2">
                                        <option value="arial" @selected(old('titleFontFamily', $config->titleFontFamily ?? 'arial') === 'arial')>Arial</option>
                                        <option value="verdana" @selected(old('titleFontFamily', $config->titleFontFamily ?? 'arial') === 'verdana')>Verdana</option>
                                        <option value="tahoma" @selected(old('titleFontFamily', $config->titleFontFamily ?? 'arial') === 'tahoma')>Tahoma</option>
                                        <option value="trebuchet" @selected(old('titleFontFamily', $config->titleFontFamily ?? 'arial') === 'trebuchet')>Trebuchet MS</option>
                                        <option value="georgia" @selected(old('titleFontFamily', $config->titleFontFamily ?? 'arial') === 'georgia')>Georgia</option>
                                        <option value="courier" @selected(old('titleFontFamily', $config->titleFontFamily ?? 'arial') === 'courier')>Courier New</option>
                                        <option value="system" @selected(old('titleFontFamily', $config->titleFontFamily ?? 'arial') === 'system')>System UI</option>
                                    </select>
                                    @error('titleFontFamily')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do texto do título</label>
                                    <input type="color" id="titleTextColor" name="titleTextColor" value="{{ old('titleTextColor', $config->titleTextColor ?? '#f8fafc') }}" class="w-full h-10 border rounded">
                                    @error('titleTextColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="isTitleBackgroundTransparent" value="0">
                                    <input type="checkbox" id="isTitleBackgroundTransparent" name="isTitleBackgroundTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isTitleBackgroundTransparent', $config->isTitleBackgroundTransparent ?? false))>
                                    <span class="text-sm text-gray-700">Deixar tarja do título transparente</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showTitleBorder" value="0">
                                    <input type="checkbox" id="showTitleBorder" name="showTitleBorder" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showTitleBorder', $config->showTitleBorder ?? true))>
                                    <span class="text-sm text-gray-700">Mostrar borda da tarja do título</span>
                                </label>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor da tarja do título</label>
                                    <input type="color" id="titleBackgroundColor" name="titleBackgroundColor" value="{{ old('titleBackgroundColor', $config->titleBackgroundColor ?? '#0f172a') }}" class="w-full h-10 border rounded">
                                    @error('titleBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                            </div>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Imagem de fundo</h4>

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

                        <div id="videoConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3 hidden">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Configuração de Vídeos</h3>
                                <button type="button" data-save-section="videoConfigSection" class="rounded-md border border-indigo-600 bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Salvar este menu</button>
                            </div>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="showVideoPanel" value="0">
                                <input type="checkbox" name="showVideoPanel" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showVideoPanel', $config->showVideoPanel ?? true))>
                                <span class="text-sm text-gray-700">Ativar vídeos da lateral direita</span>
                            </label>

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
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Configuração de Cores</h3>
                                <button type="button" data-save-section="colorConfigSection" class="rounded-md border border-indigo-600 bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Salvar este menu</button>
                            </div>
                            <p class="text-sm text-gray-600">Aqui ficam somente as cores da Totem Web.</p>

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

                        <div id="rightSidebarConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Configuração Tela Lateral Direita</h3>
                                <button type="button" data-save-section="rightSidebarConfigSection" class="rounded-md border border-indigo-600 bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Salvar este menu</button>
                            </div>
                            <p class="text-sm text-gray-600">Configurações genéricas da coluna direita da tela.</p>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Tipo de mídia</h4>
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showRightSidebarPanel" value="0">
                                    <input type="checkbox" id="showRightSidebarPanel" name="showRightSidebarPanel" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showRightSidebarPanel', $config->showRightSidebarPanel ?? true))>
                                    <span class="text-sm text-gray-700">Ativar lateral direita completa</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="isVideoPanelTransparent" value="0">
                                    <input type="checkbox" id="isVideoPanelTransparent" name="isVideoPanelTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isVideoPanelTransparent', $config->isVideoPanelTransparent ?? false))>
                                    <span class="text-sm text-gray-700">Deixar fundo da lateral transparente</span>
                                </label>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do fundo da lateral</label>
                                    <input type="color" id="videoBackgroundColor" name="videoBackgroundColor" value="{{ old('videoBackgroundColor', $config->videoBackgroundColor ?? '#000000') }}" class="w-full h-10 border rounded">
                                    @error('videoBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
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
                                        <p class="text-xs text-gray-600">Define o redimensionamento da imagem exibida no retangulo lateral direito da tela <code>/tv/totemweb</code>.</p>

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

                                        <div class="mt-2 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs text-indigo-900">
                                            Tamanho recomendado para arte da lateral (TV Full HD 1920x1080): <strong>600x920 px</strong>.
                                            <span class="block">Proporcao sugerida: <strong>9:14</strong> (largura x altura).</span>
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
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Galeria Imagem da Empresa</h3>
                                <button type="button" data-save-section="companyGalleryConfigSection" class="rounded-md border border-indigo-600 bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Salvar este menu</button>
                            </div>
                            <p class="text-sm text-gray-600">Configurações da galeria de imagens da empresa para uso na lateral direita.</p>

                            <div class="rounded-md border border-gray-200 bg-white p-3 space-y-2">
                                <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Submenu</p>
                                <div id="companyGallerySubmenuList" class="space-y-2"></div>
                                <p class="text-xs text-gray-500">Clique em um item para abrir o conteúdo.</p>
                            </div>

                            <div id="companyGalleryCodeBlock" data-company-gallery-name="Imagem da Base Geral" class="rounded-md border border-gray-200 bg-white p-4 space-y-2">
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
                                <div id="globalGalleryLookupResults" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3"></div>
                                <div id="globalGalleryLookupEmpty" class="text-xs text-amber-700 mt-2 hidden">Nenhuma imagem encontrada para este código.</div>

                                <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3 space-y-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pesquisar por nome da base</label>
                                    <input
                                        type="text"
                                        id="globalGalleryNameSearch"
                                        placeholder="Digite o nome da base"
                                        class="w-full border rounded px-3 py-2"
                                    >
                                    <div id="globalGalleryNameSearchResults" class="space-y-1"></div>
                                    <p id="globalGalleryNameSearchHint" class="text-xs text-gray-500">Digite ao menos 2 caracteres para pesquisar.</p>
                                </div>
                            </div>

                            <div id="companyGalleryLibraryBlock" data-company-gallery-name="Base de imagem propria" class="rounded-md border border-gray-200 bg-white p-3 space-y-3">
                                    <h4 class="text-sm font-semibold text-gray-800">Base de imagem propria</h4>
                                    <p class="text-xs text-gray-600">Galeria da empresa (estilo biblioteca). Clique na imagem para abrir ações: usar no produto e/ou no slide.</p>

                                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 space-y-3">
                                        <h5 class="text-sm font-semibold text-gray-800">Upload de Imagem Propria</h5>
                                        <input type="file" name="companyGalleryUpload" id="companyGalleryUpload" accept="image/*" class="w-full border rounded px-3 py-2 bg-white">
                                        @error('companyGalleryUpload')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                    </div>

                                    @if (!empty($companyGalleryImages))
                                        <div class="max-h-[420px] overflow-y-auto pr-1 border border-gray-200 rounded-md p-2 bg-gray-50">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            @foreach ($companyGalleryImages as $index => $companyImage)
                                                @php
                                                    $sourceKey = 'company_existing_' . $index;
                                                    $normalizedCompanyImageUrl = $normalizeSlideUrl((string) ($companyImage['url'] ?? ''));
                                                    $isSlideSelected = $hasOldSlideSources
                                                        ? in_array($sourceKey, (array) $oldSlideSources, true)
                                                        : $savedSlideUrls->contains($normalizedCompanyImageUrl);
                                                @endphp
                                                <div class="company-gallery-card rounded border border-gray-300 bg-gray-50 p-2 space-y-2" data-source-key="{{ $sourceKey }}">
                                                    <button type="button" class="w-full" data-company-gallery-preview="{{ $sourceKey }}">
                                                        <div class="w-full h-28 flex items-center justify-center bg-white rounded border border-transparent overflow-hidden">
                                                            <img src="{{ $companyImage['url'] }}" alt="Imagem da empresa" class="max-w-full max-h-full object-contain block mx-auto">
                                                        </div>
                                                    </button>
                                                    <p class="text-[11px] text-gray-600 truncate" title="{{ $companyImage['name'] }}">{{ $companyImage['name'] }}</p>

                                                    <div class="company-gallery-badge text-[11px] text-gray-500" data-company-gallery-badge="{{ $sourceKey }}">
                                                        @if($isSlideSelected)
                                                            Selecionada para slide
                                                        @else
                                                            Sem destino selecionado
                                                        @endif
                                                    </div>

                                                    <div class="company-gallery-actions hidden rounded border border-gray-200 bg-white p-2 space-y-2" data-company-gallery-actions="{{ $sourceKey }}">
                                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                                            <input type="checkbox" name="suggestedSlideImageSources[]" value="{{ $sourceKey }}" class="company-slide-checkbox rounded border-gray-300 text-indigo-600" data-company-slide-checkbox="{{ $sourceKey }}" data-source-url="{{ $companyImage['url'] }}" @checked($isSlideSelected)>
                                                            <span>Usar no slide</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-500">Nenhuma imagem da empresa encontrada ainda. Faça upload para começar.</p>
                                    @endif
                                </div>

                            <div id="rightSidebarImageConfig" data-company-gallery-name="Configuracao do Slide de Imagens" class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
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

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Periodo de exibicao por imagem</label>
                                    <p class="text-xs text-gray-600 mb-2">A imagem so aparece na TV dentro do intervalo definido. Se deixar inicio/fim em branco, ela pode aparecer a qualquer momento.</p>
                                    <div id="rightSidebarImageScheduleEditor" class="space-y-2"></div>
                                    <p id="rightSidebarImageScheduleHint" class="mt-2 text-xs text-gray-500">Adicione imagens para configurar datas de exibicao.</p>
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
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Fonte do produto</h3>
                                <button type="button" data-save-section="imageSizeConfigSection" class="rounded-md border border-indigo-600 bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Salvar este menu</button>
                            </div>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Tipo de lista</h4>
                                <label class="inline-flex items-center gap-2 mr-6">
                                    <input type="radio" id="productListType1" name="productListType" value="1" class="text-indigo-600 border-gray-300" @checked(old('productListType', $config->productListType ?? '1') === '1')>
                                    <span class="text-sm text-gray-700">1 lista</span>
                                </label>
                                <label id="productListType2Label" class="inline-flex items-center gap-2">
                                    <input type="radio" id="productListType2" name="productListType" value="2" class="text-indigo-600 border-gray-300" @checked(old('productListType', $config->productListType ?? '1') === '2')>
                                    <span class="text-sm text-gray-700">2 lista</span>
                                </label>
                                @error('productListType')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                <p id="productListTypeWarning" class="text-xs text-amber-700 hidden">Para usar 2 lista, desative primeiro "Ativar lateral direita completa" em Configuração Tela Lateral Direita.</p>

                                <div id="productListGroupAssignment" class="hidden mt-3 rounded-md border border-gray-200 bg-gray-50 p-3 space-y-3">
                                    <p class="text-xs text-gray-600">Escolha os grupos que devem aparecer em cada lado quando "2 lista" estiver ativo.</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h5 class="text-sm font-semibold text-gray-800 mb-2">Lado esquerdo</h5>
                                            <div class="max-h-44 overflow-y-auto rounded border border-gray-200 bg-white p-2 space-y-1">
                                                @foreach ($availableGroups as $group)
                                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                                        <input
                                                            type="checkbox"
                                                            name="productListLeftGroupIds[]"
                                                            value="{{ $group->id }}"
                                                            class="rounded border-gray-300 text-indigo-600 product-list-group-left"
                                                            data-group-id="{{ $group->id }}"
                                                            @checked(in_array((int) $group->id, array_map('intval', (array) old('productListLeftGroupIds', $config->productListLeftGroupIds ?? [])), true))
                                                        >
                                                        <span>{{ $group->nome }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('productListLeftGroupIds')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                            @error('productListLeftGroupIds.*')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <h5 class="text-sm font-semibold text-gray-800 mb-2">Lado direito</h5>
                                            <div class="max-h-44 overflow-y-auto rounded border border-gray-200 bg-white p-2 space-y-1">
                                                @foreach ($availableGroups as $group)
                                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                                        <input
                                                            type="checkbox"
                                                            name="productListRightGroupIds[]"
                                                            value="{{ $group->id }}"
                                                            class="rounded border-gray-300 text-indigo-600 product-list-group-right"
                                                            data-group-id="{{ $group->id }}"
                                                            @checked(in_array((int) $group->id, array_map('intval', (array) old('productListRightGroupIds', $config->productListRightGroupIds ?? [])), true))
                                                        >
                                                        <span>{{ $group->nome }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('productListRightGroupIds')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                            @error('productListRightGroupIds.*')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="showBorder" value="0">
                                        <input type="checkbox" name="showBorder" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showBorder', $config->showBorder))>
                                        <span class="text-sm text-gray-700">Mostrar borda da linha</span>
                                    </label>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="showImage" value="0">
                                        <input type="checkbox" name="showImage" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showImage', $config->showImage ?? true))>
                                        <span class="text-sm text-gray-700">Mostrar imagem do produto na lista</span>
                                    </label>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="isRowRoundedEnabled" value="0">
                                        <input type="checkbox" id="isRowRoundedEnabled" name="isRowRoundedEnabled" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isRowRoundedEnabled', $config->isRowRoundedEnabled ?? false))>
                                        <span class="text-sm text-gray-700">Ativar borda arredondada da linha</span>
                                    </label>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="hidden" name="isListBorderTransparent" value="0">
                                        <input type="checkbox" id="isListBorderTransparent" name="isListBorderTransparent" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('isListBorderTransparent', $config->isListBorderTransparent ?? false))>
                                        <span class="text-sm text-gray-700">Desativar borda da lista</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Grossura da borda da lista (px)</label>
                                    <input type="number" id="listBorderWidth" name="listBorderWidth" min="0" max="20" value="{{ old('listBorderWidth', $config->listBorderWidth ?? 1) }}" class="w-full border rounded px-3 py-2">
                                    @error('listBorderWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

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
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Grossura da borda da linha (px)</label>
                                    <input type="number" id="rowBorderWidth" name="rowBorderWidth" min="0" max="20" value="{{ old('rowBorderWidth', $config->rowBorderWidth ?? 1) }}" class="w-full border rounded px-3 py-2">
                                    @error('rowBorderWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho da fonte da lista (px)</label>
                                    <input type="number" name="listFontSize" min="10" max="60" value="{{ old('listFontSize', $config->listFontSize ?? 16) }}" class="w-full border rounded px-3 py-2">
                                    @error('listFontSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                            </div>

                            <div class="rounded-md border border-gray-200 bg-white p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-gray-800">Descricao grupo</h4>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho da fonte do grupo (topo) (px)</label>
                                    <input type="number" name="groupLabelFontSize" min="10" max="60" value="{{ old('groupLabelFontSize', $config->groupLabelFontSize ?? 14) }}" class="w-full border rounded px-3 py-2">
                                    @error('groupLabelFontSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de fonte da descrição do grupo</label>
                                    <select name="groupLabelFontFamily" class="w-full border rounded px-3 py-2">
                                        <option value="arial" @selected(old('groupLabelFontFamily', $config->groupLabelFontFamily ?? 'arial') === 'arial')>Arial</option>
                                        <option value="verdana" @selected(old('groupLabelFontFamily', $config->groupLabelFontFamily ?? 'arial') === 'verdana')>Verdana</option>
                                        <option value="tahoma" @selected(old('groupLabelFontFamily', $config->groupLabelFontFamily ?? 'arial') === 'tahoma')>Tahoma</option>
                                        <option value="trebuchet" @selected(old('groupLabelFontFamily', $config->groupLabelFontFamily ?? 'arial') === 'trebuchet')>Trebuchet MS</option>
                                        <option value="georgia" @selected(old('groupLabelFontFamily', $config->groupLabelFontFamily ?? 'arial') === 'georgia')>Georgia</option>
                                        <option value="courier" @selected(old('groupLabelFontFamily', $config->groupLabelFontFamily ?? 'arial') === 'courier')>Courier New</option>
                                        <option value="system" @selected(old('groupLabelFontFamily', $config->groupLabelFontFamily ?? 'arial') === 'system')>System UI</option>
                                    </select>
                                    @error('groupLabelFontFamily')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor do grupo (topo)</label>
                                    <input type="color" name="groupLabelColor" value="{{ old('groupLabelColor', $config->groupLabelColor ?? '#cbd5e1') }}" class="w-full h-10 border rounded">
                                    @error('groupLabelColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="showGroupLabelBadge" value="0">
                                    <input type="checkbox" id="showGroupLabelBadge" name="showGroupLabelBadge" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showGroupLabelBadge', $config->showGroupLabelBadge ?? false))>
                                    <span class="text-sm text-gray-700">Ativar tarja de destaque atrás da descrição</span>
                                </label>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor da tarja da descrição</label>
                                    <input type="color" id="groupLabelBadgeColor" name="groupLabelBadgeColor" value="{{ old('groupLabelBadgeColor', $config->groupLabelBadgeColor ?? '#0f172a') }}" class="w-full h-10 border rounded">
                                    @error('groupLabelBadgeColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div id="paginationConfigSection" class="config-panel rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4 hidden">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Paginação da Lista</h3>
                                <button type="button" data-save-section="paginationConfigSection" class="rounded-md border border-indigo-600 bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Salvar este menu</button>
                            </div>

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
                            <a href="{{ route('tv.totemweb') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Abrir Totem Web</a>
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
        const showGroupLabelBadge = document.getElementById('showGroupLabelBadge');
        const groupLabelBadgeColor = document.getElementById('groupLabelBadgeColor');
        const showBackgroundImage = document.getElementById('showBackgroundImage');
        const backgroundImageUrlField = document.getElementById('backgroundImageUrlField');
        const titleTextInput = document.getElementById('titleText');
        const isTitleDynamic = document.getElementById('isTitleDynamic');
        const titlePosition = document.getElementById('titlePosition');
        const titleFontSize = document.getElementById('titleFontSize');
        const titleFontFamily = document.getElementById('titleFontFamily');
        const titleTextColor = document.getElementById('titleTextColor');
        const isTitleBackgroundTransparent = document.getElementById('isTitleBackgroundTransparent');
        const showTitleBorder = document.getElementById('showTitleBorder');
        const titleBackgroundColor = document.getElementById('titleBackgroundColor');
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
        const isMainBorderEnabled = document.getElementById('isMainBorderEnabled');
        const mainBorderColor = document.getElementById('mainBorderColor');
        const mainBorderWidth = document.getElementById('mainBorderWidth');
        const rightSidebarLogoBackgroundColor = document.getElementById('rightSidebarLogoBackgroundColor');
        const isRightSidebarLogoBackgroundTransparent = document.getElementById('isRightSidebarLogoBackgroundTransparent');
        const showRightSidebarPanel = document.getElementById('showRightSidebarPanel');
        const rightSidebarMediaTypeVideo = document.getElementById('rightSidebarMediaTypeVideo');
        const rightSidebarMediaTypeImage = document.getElementById('rightSidebarMediaTypeImage');
        const rightSidebarMediaTypeHybrid = document.getElementById('rightSidebarMediaTypeHybrid');
        const rightSidebarHybridConfig = document.getElementById('rightSidebarHybridConfig');
        const rightSidebarImageConfig = document.getElementById('rightSidebarImageConfig');
        const rightSidebarImageUrls = document.getElementById('rightSidebarImageUrls');
        const rightSidebarImagePreview = document.getElementById('rightSidebarImagePreview');
        const rightSidebarImagePreviewHint = document.getElementById('rightSidebarImagePreviewHint');
        const rightSidebarImageScheduleEditor = document.getElementById('rightSidebarImageScheduleEditor');
        const rightSidebarImageScheduleHint = document.getElementById('rightSidebarImageScheduleHint');
        const rightSidebarGlobalGalleryCode = document.getElementById('rightSidebarGlobalGalleryCode');
        const globalGalleryLookupFeedback = document.getElementById('globalGalleryLookupFeedback');
        const globalGalleryLookupResults = document.getElementById('globalGalleryLookupResults');
        const globalGalleryLookupEmpty = document.getElementById('globalGalleryLookupEmpty');
        const globalGalleryNameSearch = document.getElementById('globalGalleryNameSearch');
        const globalGalleryNameSearchResults = document.getElementById('globalGalleryNameSearchResults');
        const globalGalleryNameSearchHint = document.getElementById('globalGalleryNameSearchHint');
        const companyGalleryCards = Array.from(document.querySelectorAll('.company-gallery-card'));
        const configAccordionMenu = document.getElementById('configAccordionMenu');
        const configPanelsStorage = document.getElementById('configPanelsStorage');
        const configMenuButtons = Array.from(document.querySelectorAll('.config-menu-btn'));
        const configPanels = Array.from(document.querySelectorAll('.config-panel'));
        const productListType1 = document.getElementById('productListType1');
        const productListType2 = document.getElementById('productListType2');
        const productListType2Label = document.getElementById('productListType2Label');
        const productListTypeWarning = document.getElementById('productListTypeWarning');
        const productListGroupAssignment = document.getElementById('productListGroupAssignment');
        const productListGroupLeftInputs = Array.from(document.querySelectorAll('.product-list-group-left'));
        const productListGroupRightInputs = Array.from(document.querySelectorAll('.product-list-group-right'));
        const webConfigForm = document.getElementById('webConfigForm');
        const saveSectionInput = document.getElementById('saveSection');
        const companyGallerySubmenuList = document.getElementById('companyGallerySubmenuList');
        let companyGallerySubmenuButtons = [];
        const companyGalleryNavBlocks = Array.from(document.querySelectorAll('[data-company-gallery-name][id]'));
        let activeCompanyGalleryTargetId = null;
        let openedConfigPanelId = null;
        let globalGalleryLookupTimer = null;
        let globalGalleryNameSearchTimer = null;
        let hasUserInteractedWithSlideSelection = false;
        const hasOldSlideSources = @json($hasOldSlideSources);
        const savedSlideUrls = @json($savedSlideUrls->all());
        let rightSidebarImageScheduleState = {};

        function getNormalizedScheduleStateFromInitialData() {
            const map = {};

            if (!Array.isArray(initialRightSidebarImageSchedules)) {
                return map;
            }

            initialRightSidebarImageSchedules.forEach((entry) => {
                const rawUrl = String(entry?.url || '').trim();
                const url = normalizeSlideUrlForCompare(rawUrl);
                if (!url) {
                    return;
                }

                map[url] = {
                    startDate: String(entry?.startDate || '').trim(),
                    endDate: String(entry?.endDate || '').trim(),
                };
            });

            return map;
        }
        const initialRightSidebarImageSchedules = @json(old('rightSidebarImageSchedules', $config->rightSidebarImageSchedules ?? []));

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

        function updateGroupLabelBadgeColorState() {
            if (!showGroupLabelBadge || !groupLabelBadgeColor) return;
            groupLabelBadgeColor.style.opacity = showGroupLabelBadge.checked ? '1' : '0.5';
            groupLabelBadgeColor.style.pointerEvents = showGroupLabelBadge.checked ? 'auto' : 'none';
        }

        if (showGroupLabelBadge) {
            showGroupLabelBadge.addEventListener('change', updateGroupLabelBadgeColorState);
            updateGroupLabelBadgeColorState();
        }

        function updateBackgroundImageVisibility() {
            if (!showBackgroundImage || !backgroundImageUrlField) return;
            backgroundImageUrlField.style.display = showBackgroundImage.checked ? 'block' : 'none';
        }

        if (showBackgroundImage) {
            showBackgroundImage.addEventListener('change', updateBackgroundImageVisibility);
            updateBackgroundImageVisibility();
        }

        function updateTitleConfigState() {
            const showTitleInput = document.querySelector('input[name="showTitle"][value="1"]');
            if (!(showTitleInput instanceof HTMLInputElement)) {
                return;
            }

            const enabled = showTitleInput.checked;

            if (titleTextInput) {
                titleTextInput.style.opacity = enabled ? '1' : '0.5';
                titleTextInput.style.pointerEvents = enabled ? 'auto' : 'none';
            }

            if (isTitleDynamic) {
                isTitleDynamic.disabled = !enabled;
            }

            if (titlePosition) {
                titlePosition.disabled = !enabled;
            }

            if (titleFontSize) {
                titleFontSize.disabled = !enabled;
                titleFontSize.style.opacity = enabled ? '1' : '0.5';
                titleFontSize.style.pointerEvents = enabled ? 'auto' : 'none';
            }

            if (titleFontFamily) {
                titleFontFamily.disabled = !enabled;
                titleFontFamily.style.opacity = enabled ? '1' : '0.5';
                titleFontFamily.style.pointerEvents = enabled ? 'auto' : 'none';
            }

            if (titleTextColor) {
                titleTextColor.disabled = !enabled;
                titleTextColor.style.opacity = enabled ? '1' : '0.5';
                titleTextColor.style.pointerEvents = enabled ? 'auto' : 'none';
            }

            if (isTitleBackgroundTransparent) {
                isTitleBackgroundTransparent.disabled = !enabled;
            }

            if (showTitleBorder) {
                showTitleBorder.disabled = !enabled;
            }

            updateTitleBackgroundColorState();
        }

        function updateTitleBackgroundColorState() {
            if (!titleBackgroundColor || !isTitleBackgroundTransparent) {
                return;
            }

            const showTitleInput = document.querySelector('input[name="showTitle"][value="1"]');
            const titleEnabled = showTitleInput instanceof HTMLInputElement ? showTitleInput.checked : true;
            const canUseColor = titleEnabled && !isTitleBackgroundTransparent.checked;

            titleBackgroundColor.style.opacity = canUseColor ? '1' : '0.5';
            titleBackgroundColor.style.pointerEvents = canUseColor ? 'auto' : 'none';
        }

        const showTitleInputForState = document.querySelector('input[name="showTitle"][value="1"]');
        if (showTitleInputForState instanceof HTMLInputElement) {
            showTitleInputForState.addEventListener('change', updateTitleConfigState);
            updateTitleConfigState();
        }

        if (isTitleBackgroundTransparent) {
            isTitleBackgroundTransparent.addEventListener('change', updateTitleBackgroundColorState);
            updateTitleBackgroundColorState();
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

        function updateMainBorderState() {
            if (!isMainBorderEnabled) return;
            if (mainBorderColor) {
                mainBorderColor.style.opacity = isMainBorderEnabled.checked ? '1' : '0.5';
                mainBorderColor.style.pointerEvents = isMainBorderEnabled.checked ? 'auto' : 'none';
            }
            if (mainBorderWidth) {
                mainBorderWidth.style.opacity = isMainBorderEnabled.checked ? '1' : '0.5';
                mainBorderWidth.style.pointerEvents = isMainBorderEnabled.checked ? 'auto' : 'none';
            }
        }

        if (isMainBorderEnabled) {
            isMainBorderEnabled.addEventListener('change', updateMainBorderState);
            updateMainBorderState();
        }

        function updateRightSidebarLogoBackgroundState() {
            if (!rightSidebarLogoBackgroundColor || !isRightSidebarLogoBackgroundTransparent) return;
            const enabled = !isRightSidebarLogoBackgroundTransparent.checked;
            rightSidebarLogoBackgroundColor.style.opacity = enabled ? '1' : '0.5';
            rightSidebarLogoBackgroundColor.style.pointerEvents = enabled ? 'auto' : 'none';
        }

        if (isRightSidebarLogoBackgroundTransparent) {
            isRightSidebarLogoBackgroundTransparent.addEventListener('change', updateRightSidebarLogoBackgroundState);
            updateRightSidebarLogoBackgroundState();
        }

        function updateProductListTypeAvailability() {
            if (!productListType2) return;

            const isRightSidebarActive = showRightSidebarPanel ? showRightSidebarPanel.checked : true;

            productListType2.disabled = isRightSidebarActive;
            if (productListTypeWarning) {
                productListTypeWarning.classList.toggle('hidden', !isRightSidebarActive);
            }

            if (isRightSidebarActive && productListType2.checked && productListType1) {
                productListType1.checked = true;
            }

            const canUseTwoLists = !isRightSidebarActive && Boolean(productListType2.checked);
            if (productListGroupAssignment) {
                productListGroupAssignment.classList.toggle('hidden', !canUseTwoLists);
            }
            [...productListGroupLeftInputs, ...productListGroupRightInputs].forEach((input) => {
                input.disabled = !canUseTwoLists;
            });
        }

        function notifyProductListTypeBlocked() {
            if (productListTypeWarning) {
                productListTypeWarning.classList.remove('hidden');
            }

            window.alert('Para usar 2 lista, desative antes "Ativar lateral direita completa" em Configuração Tela Lateral Direita.');
        }

        function notifyRightSidebarBlockedByTwoList() {
            window.alert('Para ativar "Ativar lateral direita completa", desative antes a opção "2 lista" em Configuracao da lista produto.');
        }

        function syncProductListGroupExclusivity(changedInput, oppositeInputs) {
            if (!(changedInput instanceof HTMLInputElement) || !changedInput.checked) {
                return;
            }

            const groupId = String(changedInput.getAttribute('data-group-id') || '');
            if (!groupId) {
                return;
            }

            oppositeInputs.forEach((input) => {
                if (String(input.getAttribute('data-group-id') || '') === groupId) {
                    input.checked = false;
                }
            });
        }

        productListGroupLeftInputs.forEach((input) => {
            input.addEventListener('change', () => syncProductListGroupExclusivity(input, productListGroupRightInputs));
        });

        productListGroupRightInputs.forEach((input) => {
            input.addEventListener('change', () => syncProductListGroupExclusivity(input, productListGroupLeftInputs));
        });

        if (showRightSidebarPanel) {
            showRightSidebarPanel.addEventListener('click', (event) => {
                const willActivate = !showRightSidebarPanel.checked;
                if (!willActivate) {
                    return;
                }

                if (!(productListType2 instanceof HTMLInputElement) || !productListType2.checked) {
                    return;
                }

                event.preventDefault();
                notifyRightSidebarBlockedByTwoList();
            });

            showRightSidebarPanel.addEventListener('change', updateProductListTypeAvailability);
        }

        if (productListType2) {
            productListType2.addEventListener('change', updateProductListTypeAvailability);

            productListType2.addEventListener('click', (event) => {
                const isRightSidebarActive = showRightSidebarPanel ? showRightSidebarPanel.checked : true;
                if (!isRightSidebarActive) {
                    return;
                }

                event.preventDefault();
                if (productListType1) {
                    productListType1.checked = true;
                }
                notifyProductListTypeBlocked();
            });
        }

        if (productListType2Label) {
            productListType2Label.addEventListener('click', (event) => {
                const isRightSidebarActive = showRightSidebarPanel ? showRightSidebarPanel.checked : true;
                if (!isRightSidebarActive) {
                    return;
                }

                event.preventDefault();
                if (productListType1) {
                    productListType1.checked = true;
                }
                notifyProductListTypeBlocked();
            });
        }

        updateProductListTypeAvailability();

        function updateRightSidebarMediaConfigState() {
            if (!rightSidebarImageConfig) return;
            const isImageMode = Boolean(rightSidebarMediaTypeImage && rightSidebarMediaTypeImage.checked);
            const isHybridMode = Boolean(rightSidebarMediaTypeHybrid && rightSidebarMediaTypeHybrid.checked);
            const isSlideSubmenuActive = activeCompanyGalleryTargetId === 'rightSidebarImageConfig';
            rightSidebarImageConfig.style.display = (isImageMode || isHybridMode) && isSlideSubmenuActive ? 'block' : 'none';

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

        function collectScheduleStateFromEditor() {
            if (!rightSidebarImageScheduleEditor) {
                return;
            }

            const rows = Array.from(rightSidebarImageScheduleEditor.querySelectorAll('[data-schedule-row-url]'));
            rows.forEach((row) => {
                const url = normalizeSlideUrlForCompare(row.getAttribute('data-schedule-row-url'));
                if (!url) {
                    return;
                }

                const startInput = row.querySelector('input[data-schedule-start]');
                const endInput = row.querySelector('input[data-schedule-end]');
                rightSidebarImageScheduleState[url] = {
                    startDate: startInput instanceof HTMLInputElement ? String(startInput.value || '').trim() : '',
                    endDate: endInput instanceof HTMLInputElement ? String(endInput.value || '').trim() : '',
                };
            });
        }

        function createScheduleInput(name, value, dataAttr) {
            const input = document.createElement('input');
            input.type = 'date';
            input.name = name;
            input.value = value;
            input.setAttribute(dataAttr, '1');
            input.className = 'w-full border rounded px-2 py-1 text-xs';
            return input;
        }

        function renderRightSidebarImageScheduleEditor() {
            if (!rightSidebarImageScheduleEditor || !rightSidebarImageScheduleHint) {
                return;
            }

            collectScheduleStateFromEditor();

            const urls = Array.from(new Set(parseRightSidebarImageUrls().map((url) => normalizeSlideUrlForCompare(url)).filter((url) => url !== '')));
            rightSidebarImageScheduleEditor.innerHTML = '';

            if (urls.length === 0) {
                rightSidebarImageScheduleHint.textContent = 'Adicione imagens para configurar datas de exibicao.';
                return;
            }

            const preservedState = {};

            urls.forEach((url, index) => {
                const state = rightSidebarImageScheduleState[url] || { startDate: '', endDate: '' };
                preservedState[url] = state;

                const row = document.createElement('div');
                row.className = 'rounded border border-gray-200 bg-gray-50 p-2 space-y-2';
                row.setAttribute('data-schedule-row-url', url);

                const urlText = document.createElement('p');
                urlText.className = 'text-[11px] text-gray-700 truncate';
                urlText.textContent = url;
                urlText.title = url;
                row.appendChild(urlText);

                const hiddenUrl = document.createElement('input');
                hiddenUrl.type = 'hidden';
                hiddenUrl.name = `rightSidebarImageSchedules[${index}][url]`;
                hiddenUrl.value = url;
                row.appendChild(hiddenUrl);

                const grid = document.createElement('div');
                grid.className = 'grid grid-cols-1 md:grid-cols-2 gap-2';

                const startWrap = document.createElement('label');
                startWrap.className = 'block text-[11px] text-gray-600';
                startWrap.textContent = 'Data inicio';
                const startInput = createScheduleInput(`rightSidebarImageSchedules[${index}][startDate]`, state.startDate, 'data-schedule-start');
                startWrap.appendChild(startInput);

                const endWrap = document.createElement('label');
                endWrap.className = 'block text-[11px] text-gray-600';
                endWrap.textContent = 'Data fim';
                const endInput = createScheduleInput(`rightSidebarImageSchedules[${index}][endDate]`, state.endDate, 'data-schedule-end');
                endWrap.appendChild(endInput);

                const syncEndMin = () => {
                    endInput.min = startInput.value || '';
                };

                startInput.addEventListener('change', () => {
                    syncEndMin();
                    rightSidebarImageScheduleState[url] = {
                        startDate: String(startInput.value || '').trim(),
                        endDate: String(endInput.value || '').trim(),
                    };
                });

                endInput.addEventListener('change', () => {
                    rightSidebarImageScheduleState[url] = {
                        startDate: String(startInput.value || '').trim(),
                        endDate: String(endInput.value || '').trim(),
                    };
                });

                syncEndMin();
                grid.appendChild(startWrap);
                grid.appendChild(endWrap);
                row.appendChild(grid);

                rightSidebarImageScheduleEditor.appendChild(row);
            });

            rightSidebarImageScheduleState = preservedState;
            rightSidebarImageScheduleHint.textContent = `${urls.length} imagem(ns) com periodo configuravel.`;
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

        function isSavedSlideUrl(url) {
            const normalized = normalizeSlideUrlForCompare(url);
            if (!normalized) {
                return false;
            }

            return savedSlideUrls.some((item) => normalizeSlideUrlForCompare(item) === normalized);
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
            renderRightSidebarImageScheduleEditor();
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
            rightSidebarImageUrls.addEventListener('input', () => {
                renderRightSidebarImagePreview();
                renderRightSidebarImageScheduleEditor();
            });
        }

        updateRightSidebarMediaConfigState();
        rightSidebarImageScheduleState = getNormalizedScheduleStateFromInitialData();
        renderRightSidebarImagePreview();
        renderRightSidebarImageScheduleEditor();

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

                return;
            }

            const oldSlideSources = new Set(getOldSuggestedSlideSources());

            globalGalleryLookupFeedback.textContent = `Código ${payload.code} encontrado: ${payload.name}.`;

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

                const slideLabel = document.createElement('label');
                slideLabel.className = 'inline-flex items-center gap-2 text-xs text-gray-700';
                const isChecked = hasOldSlideSources ? oldSlideSources.has(slotKey) : isSavedSlideUrl(url);
                slideLabel.innerHTML = `<input type="checkbox" name="suggestedSlideImageSources[]" value="${slotKey}" data-source-url="${url}" class="rounded border-gray-300 text-indigo-600" ${isChecked ? 'checked' : ''}><span>Usar no slide</span>`;
                card.appendChild(slideLabel);

                globalGalleryLookupResults.appendChild(card);
            });

            if (!globalGalleryLookupResults.children.length) {
                globalGalleryLookupEmpty.classList.remove('hidden');
                globalGalleryLookupFeedback.textContent = 'Código encontrado, mas sem imagens válidas.';
            }

        }

        function renderGlobalGalleryNameSearchResults(items) {
            if (!globalGalleryNameSearchResults || !globalGalleryNameSearchHint) {
                return;
            }

            globalGalleryNameSearchResults.innerHTML = '';

            if (!Array.isArray(items) || items.length === 0) {
                globalGalleryNameSearchHint.textContent = 'Nenhuma base encontrada para o nome informado.';
                return;
            }

            globalGalleryNameSearchHint.textContent = `${items.length} base(s) encontrada(s).`;

            items.forEach((item) => {
                const code = String(item?.code || '').trim();
                const name = String(item?.name || '').trim();

                if (code === '') {
                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left rounded border border-gray-200 px-3 py-2 hover:bg-white';
                button.innerHTML = `<span class="text-xs font-semibold text-gray-700">${name || 'Sem nome'}</span><span class="block text-[11px] text-gray-500">Código: ${code}</span>`;
                button.addEventListener('click', () => {
                    if (!rightSidebarGlobalGalleryCode) {
                        return;
                    }

                    rightSidebarGlobalGalleryCode.value = code;
                    globalGalleryNameSearchResults.innerHTML = '';
                    globalGalleryNameSearchHint.textContent = `Selecionado: ${name || 'Sem nome'} (código ${code}).`;
                    lookupGlobalGalleryByCode();
                });

                globalGalleryNameSearchResults.appendChild(button);
            });
        }

        async function searchGlobalGalleryByName() {
            if (!globalGalleryNameSearch || !globalGalleryNameSearchResults || !globalGalleryNameSearchHint) {
                return;
            }

            const query = String(globalGalleryNameSearch.value || '').trim();

            if (query.length < 2) {
                globalGalleryNameSearchResults.innerHTML = '';
                globalGalleryNameSearchHint.textContent = 'Digite ao menos 2 caracteres para pesquisar.';
                return;
            }

            try {
                const response = await fetch(`{{ route('admin.global-image-galleries.search-by-name') }}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                const payload = await response.json();
                renderGlobalGalleryNameSearchResults(payload?.items || []);
            } catch (_error) {
                globalGalleryNameSearchHint.textContent = 'Falha ao pesquisar base por nome.';
            }
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

        if (globalGalleryNameSearch) {
            globalGalleryNameSearch.addEventListener('input', () => {
                if (globalGalleryNameSearchTimer) {
                    clearTimeout(globalGalleryNameSearchTimer);
                }

                globalGalleryNameSearchTimer = setTimeout(() => {
                    searchGlobalGalleryByName();
                }, 300);
            });
        }

        function updateCompanyGalleryCardStates() {
            companyGalleryCards.forEach((card) => {
                const sourceKey = String(card.getAttribute('data-source-key') || '');
                if (!sourceKey) {
                    return;
                }

                const badge = card.querySelector(`[data-company-gallery-badge="${sourceKey}"]`);
                const slideCheckbox = card.querySelector(`[data-company-slide-checkbox="${sourceKey}"]`);
                const image = card.querySelector('img');

                const isSlideSelected = Boolean(slideCheckbox && slideCheckbox.checked);

                card.classList.toggle('border-indigo-500', isSlideSelected);
                card.classList.toggle('bg-indigo-50', isSlideSelected);

                if (image) {
                    image.classList.toggle('ring-2', isSlideSelected);
                    image.classList.toggle('ring-indigo-500', isSlideSelected);
                }

                if (badge) {
                    if (isSlideSelected) {
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

        document.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
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

        });

        updateCompanyGalleryCardStates();

        function setCompanyGallerySubmenuButtonState(button, isActive) {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.classList.toggle('bg-indigo-100', isActive);
            button.classList.toggle('text-indigo-700', isActive);
            button.classList.toggle('font-semibold', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
        }

        function setCompanyGalleryActiveBlock(targetId, triggerButton = null) {
            companyGalleryNavBlocks.forEach((block) => {
                const isActive = block.id === targetId;
                block.classList.toggle('hidden', !isActive);
                block.style.display = isActive ? '' : 'none';
            });

            if (triggerButton instanceof HTMLElement) {
                const targetBlock = companyGalleryNavBlocks.find((block) => block.id === targetId);
                if (targetBlock) {
                    triggerButton.insertAdjacentElement('afterend', targetBlock);
                }
            }
        }

        function closeAllCompanyGalleryBlocks() {
            activeCompanyGalleryTargetId = null;
            companyGalleryNavBlocks.forEach((block) => {
                block.classList.add('hidden');
                block.style.display = 'none';
            });
            companyGallerySubmenuButtons.forEach((button) => setCompanyGallerySubmenuButtonState(button, false));
        }

        function buildCompanyGallerySubmenuButtons() {
            if (!companyGallerySubmenuList) {
                return;
            }

            companyGallerySubmenuList.innerHTML = '';

            companyGalleryNavBlocks.forEach((block) => {
                const targetId = String(block.id || '').trim();
                const label = String(block.getAttribute('data-company-gallery-name') || '').trim();

                if (!targetId || !label) {
                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'company-gallery-submenu-btn w-full rounded border border-gray-300 px-3 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50';
                button.setAttribute('data-company-gallery-target', targetId);
                button.textContent = label;

                button.addEventListener('click', () => {
                    const clickedTargetId = String(button.getAttribute('data-company-gallery-target') || '').trim();
                    if (!clickedTargetId) {
                        return;
                    }

                    if (activeCompanyGalleryTargetId === clickedTargetId) {
                        closeAllCompanyGalleryBlocks();
                        return;
                    }

                    activeCompanyGalleryTargetId = clickedTargetId;
                    setCompanyGalleryActiveBlock(clickedTargetId, button);
                    companyGallerySubmenuButtons.forEach((submenuButton) => {
                        setCompanyGallerySubmenuButtonState(submenuButton, submenuButton === button);
                    });
                    updateRightSidebarMediaConfigState();
                });

                companyGallerySubmenuList.appendChild(button);
            });

            companyGallerySubmenuButtons = Array.from(companyGallerySubmenuList.querySelectorAll('.company-gallery-submenu-btn'));
        }

        buildCompanyGallerySubmenuButtons();
        closeAllCompanyGalleryBlocks();

        function setConfigMenuButtonState(button, isActive) {
            if (!button) return;

            button.classList.toggle('bg-indigo-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-indigo-600', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
            button.classList.toggle('border-gray-300', !isActive);
        }

        function setConfigPanelState(panel, isActive) {
            if (!panel) return;

            panel.classList.toggle('bg-indigo-50', isActive);
            panel.classList.toggle('border-indigo-200', isActive);
            panel.classList.toggle('bg-gray-50', !isActive);
            panel.classList.toggle('border-gray-200', !isActive);
        }

        function closeAllConfigPanels() {
            configPanels.forEach((panel) => {
                panel.classList.add('hidden');
                setConfigPanelState(panel, false);
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
            setConfigPanelState(targetPanel, true);
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

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const saveButton = target.closest('[data-save-section]');
            if (!saveButton || !(saveButton instanceof HTMLButtonElement)) {
                return;
            }

            const sectionId = String(saveButton.getAttribute('data-save-section') || '').trim();
            if (!webConfigForm || !saveSectionInput || !sectionId) {
                return;
            }

            const sectionPanel = document.getElementById(sectionId);
            if (!sectionPanel) {
                return;
            }

            const formControls = Array.from(webConfigForm.querySelectorAll('input, select, textarea, button'));
            const sectionControls = new Set(Array.from(sectionPanel.querySelectorAll('input, select, textarea, button')));
            const controlsState = [];

            formControls.forEach((control) => {
                const isToken = control instanceof HTMLInputElement && control.name === '_token';
                const isSaveSection = control === saveSectionInput;
                const shouldKeep = sectionControls.has(control) || isToken || isSaveSection;

                controlsState.push({ control, disabled: control.disabled });
                if (!shouldKeep) {
                    control.disabled = true;
                }
            });

            saveSectionInput.value = sectionId;

            // Restore control states if browser blocks submission due to validation.
            const restoreStates = () => {
                controlsState.forEach(({ control, disabled }) => {
                    control.disabled = disabled;
                });
            };

            webConfigForm.addEventListener('invalid', restoreStates, { once: true, capture: true });
            setTimeout(restoreStates, 50);
            webConfigForm.requestSubmit();
        });

        configMenuButtons.forEach((button) => setConfigMenuButtonState(button, false));

        const initialTarget = @json($hasVideoValidationErrors ? 'videoConfigSection' : null);
        if (initialTarget) {
            openConfigPanel(initialTarget);
        }
    </script>
</x-app-layout>
