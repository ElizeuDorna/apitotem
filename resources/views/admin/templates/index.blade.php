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
                    <div class="mb-5 rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                        <h3 class="text-sm font-semibold text-sky-900">Como funciona esta tela</h3>
                        <div class="mt-2 grid gap-3 text-sm text-sky-800 md:grid-cols-3">
                            <div class="rounded-lg border border-sky-100 bg-white/80 p-3">
                                <p class="font-semibold">Template padrão</p>
                                <p class="mt-1 text-xs leading-5 text-sky-700">Usado como padrão da Totem Web para a empresa quando houver snapshot salvo.</p>
                            </div>
                            <div class="rounded-lg border border-sky-100 bg-white/80 p-3">
                                <p class="font-semibold">Snapshot Totem Web</p>
                                <p class="mt-1 text-xs leading-5 text-sky-700">Copia a configuração atual da tela web para dentro do template.</p>
                            </div>
                            <div class="rounded-lg border border-sky-100 bg-white/80 p-3">
                                <p class="font-semibold">Aplicar em TV</p>
                                <p class="mt-1 text-xs leading-5 text-sky-700">Vincula este template a uma TV específica da empresa.</p>
                            </div>
                        </div>
                    </div>

                    @if ($adminSemEmpresaAtiva ?? false)
                        <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-800 mb-4">
                            Selecione uma empresa ativa em Empresas para gerenciar templates da Totem Web.
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

                    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Template padrão atual</p>
                        @if ($defaultTemplate)
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-800">
                                <strong>{{ $defaultTemplate->nome }}</strong>
                                <span
                                    class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700"
                                    data-layout-label
                                    data-layout-key="{{ $defaultTemplate->tipo_layout }}"
                                    data-label-pt="{{ $layoutLabels[$defaultTemplate->tipo_layout]['pt'] ?? $defaultTemplate->tipo_layout }}"
                                    data-label-en="{{ $layoutLabels[$defaultTemplate->tipo_layout]['en'] ?? $defaultTemplate->tipo_layout }}"
                                >{{ $layoutLabels[$defaultTemplate->tipo_layout]['pt'] ?? $defaultTemplate->tipo_layout }}</span>
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ !empty($defaultTemplate->web_config_payload) ? 'Snapshot capturado' : 'Sem snapshot' }}</span>
                            </div>
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
                                    <th class="px-3 py-2 text-left">Template</th>
                                    <th class="px-3 py-2 text-left">Situação</th>
                                    <th class="px-3 py-2 text-left">Uso</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($templates as $template)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900">{{ $template->nome }}</div>
                                            <div class="mt-1">
                                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700" data-layout-label data-layout-key="{{ $template->tipo_layout }}" data-label-pt="{{ $layoutLabels[$template->tipo_layout]['pt'] ?? $template->tipo_layout }}" data-label-en="{{ $layoutLabels[$template->tipo_layout]['en'] ?? $template->tipo_layout }}">{{ $layoutLabels[$template->tipo_layout]['pt'] ?? $template->tipo_layout }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex flex-wrap gap-2">
                                                @if($template->is_default_web)
                                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Padrão da empresa</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Nao padrao</span>
                                                @endif

                                                @if(!empty($template->web_config_payload))
                                                    <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">Snapshot capturado</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Sem snapshot</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="text-sm text-slate-800">{{ $template->device_configurations_count }} {{ $template->device_configurations_count === 1 ? 'TV vinculada' : 'TVs vinculadas' }}</div>
                                            <div class="mt-1 text-xs text-slate-500">O template padrão afeta a Totem Web; o vínculo em TV afeta dispositivos específicos.</div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex flex-col items-end gap-3">
                                                <div class="flex flex-wrap justify-end gap-3 text-sm">
                                                    <a href="{{ route('admin.templates.edit', $template) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Abrir configuração</a>

                                                    <form method="POST" action="{{ route('admin.templates.set-default-web', $template) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="font-medium text-emerald-600 hover:text-emerald-800">Definir como padrão</button>
                                                    </form>
                                                </div>

                                                @if($devices->isNotEmpty())
                                                    <form method="POST" action="{{ route('admin.templates.apply-device', $template) }}" class="flex flex-wrap items-center justify-end gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                                        @csrf
                                                        <label for="device_id_{{ $template->id }}" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Aplicar em TV</label>
                                                        <select id="device_id_{{ $template->id }}" name="device_id" class="rounded-md border-gray-300 text-xs" required>
                                                            <option value="">Selecione uma TV</option>
                                                            @foreach($devices as $device)
                                                                <option value="{{ $device->id }}">{{ $device->nome }}{{ $device->local ? ' - ' . $device->local : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="rounded-md bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-800">Aplicar</button>
                                                    </form>
                                                @else
                                                    <div class="text-xs text-slate-500">Nenhuma TV disponível para vincular.</div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-8 text-center text-gray-500">Nenhum template cadastrado.</td>
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
