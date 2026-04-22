<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Template</h2>
    </x-slot>

    @php
        $layoutLabels = [
            'grade' => ['pt' => 'Grade', 'en' => 'Grid'],
            'lista' => ['pt' => 'Lista', 'en' => 'List'],
            'video_background' => ['pt' => 'Video de fundo', 'en' => 'Video background'],
            'promocao' => ['pt' => 'Promocao', 'en' => 'Promotion'],
            'misto' => ['pt' => 'Misto', 'en' => 'Mixed'],
        ];

        $typeLabels = [
            'produto_lista' => 'Lista de produtos',
            'imagem' => 'Imagem',
            'video' => 'Video',
            'banner' => 'Banner',
            'texto' => 'Texto',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                <h3 class="text-sm font-semibold text-sky-900">Leitura rapida deste template</h3>
                <div class="mt-2 grid gap-3 md:grid-cols-4">
                    <div class="rounded-lg border border-sky-100 bg-white/80 p-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Layout</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $layoutLabels[$template->tipo_layout]['pt'] ?? $template->tipo_layout }}</p>
                    </div>
                    <div class="rounded-lg border border-sky-100 bg-white/80 p-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Snapshot Web</p>
                        <p class="mt-1 text-sm font-semibold {{ empty($template->web_config_payload) ? 'text-amber-700' : 'text-emerald-700' }}">{{ empty($template->web_config_payload) ? 'Nao capturado' : 'Capturado' }}</p>
                    </div>
                    <div class="rounded-lg border border-sky-100 bg-white/80 p-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Template padrao</p>
                        <p class="mt-1 text-sm font-semibold {{ $template->is_default_web ? 'text-emerald-700' : 'text-slate-700' }}">{{ $template->is_default_web ? 'Sim, para esta empresa' : 'Nao' }}</p>
                    </div>
                    <div class="rounded-lg border border-sky-100 bg-white/80 p-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">TV vinculada</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $assignedDeviceId ? 'Sim' : 'Nao' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

                    <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <p class="font-semibold text-slate-900">Dados principais do template</p>
                        <p class="mt-1">Aqui voce define nome, layout, snapshot da Totem Web, padrao da empresa e TV vinculada.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.templates.update', $template) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input name="nome" type="text" value="{{ old('nome', $template->nome) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Layout</label>
                            <select id="tipoLayoutSelect" name="tipo_layout" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach($layouts as $layout)
                                    <option
                                        value="{{ $layout }}"
                                        data-layout-key="{{ $layout }}"
                                        data-label-pt="{{ $layoutLabels[$layout]['pt'] ?? $layout }}"
                                        data-label-en="{{ $layoutLabels[$layout]['en'] ?? $layout }}"
                                        @selected(old('tipo_layout', $template->tipo_layout) === $layout)
                                    >{{ $layoutLabels[$layout]['pt'] ?? $layout }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Aplicar em dispositivo</label>
                            <select name="device_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Nao alterar dispositivo</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}" @selected((string) old('device_id', $assignedDeviceId) === (string) $device->id)>
                                        {{ $device->nome }}{{ $device->local ? ' - ' . $device->local : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('device_id')" class="mt-2" />
                        </div>

                        <div class="md:col-span-3 flex flex-wrap items-center gap-4">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="capture_web_config" value="0">
                                <input type="checkbox" name="capture_web_config" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('capture_web_config') === '1')>
                                <span class="text-sm text-gray-700">Atualizar snapshot com a configuração atual da Totem Web</span>
                            </label>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="is_default_web" value="0">
                                <input type="checkbox" name="is_default_web" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_default_web', $template->is_default_web) == '1')>
                                <span class="text-sm text-gray-700">Definir este template como padrão da empresa</span>
                            </label>
                        </div>

                        <div class="md:col-span-3 rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            Snapshot Totem Web: <strong>{{ empty($template->web_config_payload) ? 'Nao capturado' : 'Capturado' }}</strong>. Se voce marcar a caixa de atualizar snapshot, o template guarda uma copia da configuracao atual da tela web.
                        </div>

                        <div class="flex items-end gap-2">
                            <x-primary-button>Salvar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <h3 class="font-semibold">Adicionar bloco</h3>
                        <p class="mt-1 text-sm text-slate-600">Os blocos controlam o conteudo exibido pela TV dentro deste template.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.templates.items.store', $template) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tipo de bloco</label>
                            <select name="tipo" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo }}">{{ $typeLabels[$tipo] ?? $tipo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Ordem</label>
                            <input name="ordem" type="number" min="1" value="{{ old('ordem', 1) }}" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="1" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Conteudo</label>
                            <input name="conteudo" type="text" value="{{ old('conteudo') }}" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="URL, texto ou referencia">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tempo de exibicao</label>
                            <input name="tempo_exibicao" type="number" min="1" max="600" value="{{ old('tempo_exibicao') }}" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="Segundos">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Tamanho</label>
                            <input name="tamanho" type="text" value="{{ old('tamanho') }}" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="Ex: full, 50%">
                        </div>
                        <div class="md:col-span-5 flex justify-end">
                            <x-primary-button>Adicionar bloco</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <h3 class="font-semibold">Blocos do template</h3>
                        <p class="mt-1 text-sm text-slate-600">Veja abaixo a ordem de exibicao e as configuracoes de cada bloco.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Ordem</th>
                                    <th class="px-3 py-2 text-left">Tipo</th>
                                    <th class="px-3 py-2 text-left">Conteúdo</th>
                                    <th class="px-3 py-2 text-left">Config</th>
                                    <th class="px-3 py-2 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($items as $item)
                                    <tr>
                                        <td class="px-3 py-2">{{ $item->ordem }}</td>
                                        <td class="px-3 py-2">{{ $typeLabels[$item->tipo] ?? $item->tipo }}</td>
                                        <td class="px-3 py-2">
                                            <div class="max-w-md break-words text-slate-800">{{ $item->conteudo ?: '-' }}</div>
                                        </td>
                                        <td class="px-3 py-2">
                                            @php
                                                $itemConfig = (array) ($item->config_json ?? []);
                                            @endphp
                                            @if(empty($itemConfig))
                                                <span class="text-slate-500">Sem configuracao extra</span>
                                            @else
                                                <div class="flex flex-wrap gap-2">
                                                    @if(!empty($itemConfig['tempo_exibicao']))
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">Tempo: {{ $itemConfig['tempo_exibicao'] }}s</span>
                                                    @endif
                                                    @if(!empty($itemConfig['tamanho']))
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">Tamanho: {{ $itemConfig['tamanho'] }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST" action="{{ route('admin.templates.items.destroy', [$template, $item]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">Sem blocos neste template.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <a href="{{ route('admin.templates.index') }}" class="text-sm text-gray-600 underline">Voltar</a>
                <form method="POST" action="{{ route('admin.templates.destroy', $template) }}" onsubmit="return confirm('Remover template?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-600 hover:text-red-800 text-sm" type="submit">Excluir template</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const select = document.getElementById('tipoLayoutSelect');
            if (!select) {
                return;
            }

            const isEnglishBrowser = String(navigator.language || '').toLowerCase().startsWith('en');

            Array.from(select.options).forEach((option) => {
                const labelPt = String(option.getAttribute('data-label-pt') || option.value);
                const labelEn = String(option.getAttribute('data-label-en') || option.value);
                option.textContent = isEnglishBrowser ? labelEn : labelPt;
            });
        })();
    </script>
</x-app-layout>
