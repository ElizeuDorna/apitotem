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
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

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
                                <span class="text-sm text-gray-700">Atualizar snapshot com configuração atual da Totem Web</span>
                            </label>

                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="is_default_web" value="0">
                                <input type="checkbox" name="is_default_web" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_default_web', $template->is_default_web) == '1')>
                                <span class="text-sm text-gray-700">Template padrão da empresa</span>
                            </label>
                        </div>

                        <div class="md:col-span-3 rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            Snapshot Totem Web: <strong>{{ empty($template->web_config_payload) ? 'Nao capturado' : 'Capturado' }}</strong>
                        </div>

                        <div class="flex items-end gap-2">
                            <x-primary-button>Salvar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-semibold mb-4">Adicionar bloco</h3>
                    <form method="POST" action="{{ route('admin.templates.items.store', $template) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        @csrf
                        <select name="tipo" class="rounded-md border-gray-300 shadow-sm" required>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo }}">{{ $tipo }}</option>
                            @endforeach
                        </select>
                        <input name="ordem" type="number" min="1" value="{{ old('ordem', 1) }}" class="rounded-md border-gray-300 shadow-sm" placeholder="Ordem" required>
                        <input name="conteudo" type="text" value="{{ old('conteudo') }}" class="rounded-md border-gray-300 shadow-sm" placeholder="Conteúdo (URL/texto)">
                        <input name="tempo_exibicao" type="number" min="1" max="600" value="{{ old('tempo_exibicao') }}" class="rounded-md border-gray-300 shadow-sm" placeholder="Tempo (s)">
                        <input name="tamanho" type="text" value="{{ old('tamanho') }}" class="rounded-md border-gray-300 shadow-sm" placeholder="Tamanho (ex: full, 50%)">
                        <div class="md:col-span-5 flex justify-end">
                            <x-primary-button>Adicionar bloco</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-semibold mb-4">Blocos do template</h3>
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
                                        <td class="px-3 py-2">{{ $item->tipo }}</td>
                                        <td class="px-3 py-2">{{ $item->conteudo }}</td>
                                        <td class="px-3 py-2">{{ json_encode($item->config_json) }}</td>
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
