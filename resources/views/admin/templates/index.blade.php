<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Template</h2>
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($adminSemEmpresaAtiva ?? false)
                        <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-800 mb-4">
                            Selecione uma empresa ativa em Empresas para gerenciar templates da Totem Web.
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

                    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Template padrão</p>
                        @if ($defaultTemplate)
                            <p class="mt-1 text-sm text-slate-800">
                                <strong>{{ $defaultTemplate->nome }}</strong>
                                <span
                                    class="text-slate-500"
                                    data-layout-label
                                    data-layout-key="{{ $defaultTemplate->tipo_layout }}"
                                    data-label-pt="{{ $layoutLabels[$defaultTemplate->tipo_layout]['pt'] ?? $defaultTemplate->tipo_layout }}"
                                    data-label-en="{{ $layoutLabels[$defaultTemplate->tipo_layout]['en'] ?? $defaultTemplate->tipo_layout }}"
                                >({{ $layoutLabels[$defaultTemplate->tipo_layout]['pt'] ?? $defaultTemplate->tipo_layout }})</span>
                            </p>
                        @else
                            <p class="mt-1 text-sm text-slate-600">Nenhum template padrão definido.</p>
                        @endif
                    </div>

                    <div class="mb-4 text-right">
                        <a href="{{ route('admin.templates.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm hover:bg-indigo-700">Novo Template</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Nome</th>
                                    <th class="px-3 py-2 text-left">Layout</th>
                                    <th class="px-3 py-2 text-left">Padrão</th>
                                    <th class="px-3 py-2 text-left">Snapshot Totem Web</th>
                                    <th class="px-3 py-2 text-left">TVs aplicadas</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($templates as $template)
                                    <tr>
                                        <td class="px-3 py-2">{{ $template->nome }}</td>
                                        <td class="px-3 py-2" data-layout-label data-layout-key="{{ $template->tipo_layout }}" data-label-pt="{{ $layoutLabels[$template->tipo_layout]['pt'] ?? $template->tipo_layout }}" data-label-en="{{ $layoutLabels[$template->tipo_layout]['en'] ?? $template->tipo_layout }}">{{ $layoutLabels[$template->tipo_layout]['pt'] ?? $template->tipo_layout }}</td>
                                        <td class="px-3 py-2">
                                            @if($template->is_default_web)
                                                <span class="inline-flex rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Sim</span>
                                            @else
                                                <span class="text-slate-500">Nao</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            @if(!empty($template->web_config_payload))
                                                <span class="inline-flex rounded bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">Capturado</span>
                                            @else
                                                <span class="text-slate-500">Vazio</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ $template->device_configurations_count }}</td>
                                        <td class="px-3 py-2 text-right space-x-3">
                                            <a href="{{ route('admin.templates.edit', $template) }}" class="text-indigo-600 hover:text-indigo-800">Editar</a>

                                            <form method="POST" action="{{ route('admin.templates.set-default-web', $template) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-emerald-600 hover:text-emerald-800">Definir padrão</button>
                                            </form>

                                            @if($devices->isNotEmpty())
                                                <form method="POST" action="{{ route('admin.templates.apply-device', $template) }}" class="inline-flex items-center gap-2 ml-3">
                                                    @csrf
                                                    <select name="device_id" class="rounded-md border-gray-300 text-xs" required>
                                                        <option value="">Aplicar em TV</option>
                                                        @foreach($devices as $device)
                                                            <option value="{{ $device->id }}">{{ $device->nome }}{{ $device->local ? ' - ' . $device->local : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="rounded-md bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-800">Aplicar</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-8 text-center text-gray-500">Nenhum template cadastrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $templates->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const isEnglishBrowser = String(navigator.language || '').toLowerCase().startsWith('en');
            const labels = document.querySelectorAll('[data-layout-label]');

            labels.forEach((element) => {
                const labelPt = String(element.getAttribute('data-label-pt') || element.getAttribute('data-layout-key') || '');
                const labelEn = String(element.getAttribute('data-label-en') || element.getAttribute('data-layout-key') || '');

                if (element.tagName.toLowerCase() === 'span' && element.textContent && element.textContent.includes('(')) {
                    element.textContent = `(${isEnglishBrowser ? labelEn : labelPt})`;
                    return;
                }

                element.textContent = isEnglishBrowser ? labelEn : labelPt;
            });
        })();
    </script>
</x-app-layout>
