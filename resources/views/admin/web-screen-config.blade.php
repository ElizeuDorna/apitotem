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

                    <form method="POST" action="{{ route('admin.web-screen-config.update') }}" class="space-y-5">
                        @csrf

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800">Vídeo da Tela Web</h3>
                                    <p class="text-xs text-gray-500">O formulário de vídeo fica oculto para ocupar menos espaço.</p>
                                </div>
                                <button id="toggleVideoConfig" type="button" class="rounded-md border border-indigo-600 px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50">
                                    Configurar vídeos
                                </button>
                            </div>
                        </div>

                        <div id="videoConfigSection" class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3 {{ $hasVideoValidationErrors ? '' : 'hidden' }}">
                            <h3 class="text-base font-semibold text-gray-800">Configuração de Vídeos</h3>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="showVideoPanel" value="0">
                                <input type="checkbox" name="showVideoPanel" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('showVideoPanel', $config->showVideoPanel ?? true))>
                                <span class="text-sm text-gray-700">Ativar tela de vídeo (geral)</span>
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

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4">
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

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4">
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

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4">
                            <h3 class="text-base font-semibold text-gray-800">Tamanho da Imagem do Produto</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Largura (px)</label>
                                    <input type="number" name="imageWidth" min="20" max="400" value="{{ old('imageWidth', $config->imageWidth ?? 56) }}" class="w-full border rounded px-3 py-2">
                                    @error('imageWidth')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Altura (px)</label>
                                    <input type="number" name="imageHeight" min="20" max="400" value="{{ old('imageHeight', $config->imageHeight ?? 56) }}" class="w-full border rounded px-3 py-2">
                                    @error('imageHeight')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
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

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-4">
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
        const toggleVideoConfig = document.getElementById('toggleVideoConfig');
        const videoConfigSection = document.getElementById('videoConfigSection');

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
            if (!isRowBorderTransparent || !borderColor) return;
            borderColor.style.opacity = isRowBorderTransparent.checked ? '0.5' : '1';
            borderColor.style.pointerEvents = isRowBorderTransparent.checked ? 'none' : 'auto';
        }

        if (isRowBorderTransparent) {
            isRowBorderTransparent.addEventListener('change', updateBorderColorState);
            updateBorderColorState();
        }

        function updateListBorderColorState() {
            if (!isListBorderTransparent || !listBorderColor) return;
            listBorderColor.style.opacity = isListBorderTransparent.checked ? '0.5' : '1';
            listBorderColor.style.pointerEvents = isListBorderTransparent.checked ? 'none' : 'auto';
        }

        if (isListBorderTransparent) {
            isListBorderTransparent.addEventListener('change', updateListBorderColorState);
            updateListBorderColorState();
        }

        if (toggleVideoConfig && videoConfigSection) {
            toggleVideoConfig.addEventListener('click', () => {
                videoConfigSection.classList.toggle('hidden');
            });
        }
    </script>
</x-app-layout>
