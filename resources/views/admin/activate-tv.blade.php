<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ativar TV
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-5">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($isDefaultAdmin && ($adminSemEmpresaAtiva ?? false))
                        <div class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                            Selecione uma empresa ativa em Empresas para usar Ativar TV.
                        </div>
                    @endif

                    @if ($activatedToken)
                        <div class="rounded-md bg-blue-50 p-4">
                            <p class="text-xs text-blue-700">Token gerado para a TV</p>
                            <textarea rows="3" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $activatedToken }}</textarea>
                            @if (session('activated_device_uuid'))
                                <p class="mt-3 text-xs text-blue-700">Identificacao do dispositivo</p>
                                <input type="text" readonly value="{{ session('activated_device_uuid') }}" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 font-mono text-xs text-gray-700 shadow-sm">
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.activate-device') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">Código mostrado na TV</label>
                            <input id="code" name="code" type="text" maxlength="10" value="{{ old('code') }}" class="mt-1 block w-full rounded-md border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        @if ($isDefaultAdmin)
                            <div class="rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                                Empresa ativa: <strong>{{ $empresaAtiva?->NOME ?? $empresaAtiva?->nome }}</strong>
                                ({{ $empresaAtiva?->CNPJ_CPF ?? $empresaAtiva?->cnpj_cpf }})
                            </div>
                        @else
                            <div class="rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                                Empresa vinculada: <strong>{{ $empresaVinculada?->NOME }}</strong>
                                ({{ $empresaVinculada?->CNPJ_CPF }})
                            </div>
                        @endif

                        <div>
                            <label for="nome_tv" class="block text-sm font-medium text-gray-700">Nome da TV</label>
                            <input id="nome_tv" name="nome_tv" type="text" value="{{ old('nome_tv') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('nome_tv')" class="mt-2" />
                        </div>

                        <div>
                            <label for="local" class="block text-sm font-medium text-gray-700">Local/Setor</label>
                            <input id="local" name="local" type="text" value="{{ old('local') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('local')" class="mt-2" />
                        </div>

                        <div>
                            <label for="web_screen_model_id" class="block text-sm font-medium text-gray-700">Qual modelo esta TV vai usar</label>
                            <select id="web_screen_model_id" name="web_screen_model_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Usar configuracao geral da empresa</option>
                                @foreach ($activationModels as $model)
                                    <option value="{{ $model->id }}" @selected((string) old('web_screen_model_id') === (string) $model->id)>{{ $model->nome }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">O dispositivo vai carregar o modelo escolhido aqui.</p>
                            <x-input-error :messages="$errors->get('web_screen_model_id')" class="mt-2" />
                        </div>

                        <div>
                            <label for="product_department_id" class="block text-sm font-medium text-gray-700">Departamento dos produtos deste dispositivo</label>
                            <select id="product_department_id" name="product_department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos os departamentos</option>
                                @foreach ($activationDepartments as $department)
                                    <option value="{{ $department->id }}" @selected((string) old('product_department_id') === (string) $department->id)>{{ $department->nome }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Se escolher um departamento, esta TV receberá apenas produtos dele.</p>
                            <x-input-error :messages="$errors->get('product_department_id')" class="mt-2" />
                        </div>

                        <div>
                            <label for="product_group_id" class="block text-sm font-medium text-gray-700">Grupo dos produtos deste dispositivo</label>
                            <select id="product_group_id" name="product_group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos os grupos</option>
                                @foreach ($activationGroups as $group)
                                    <option value="{{ $group->id }}" data-department-id="{{ $group->departamento_id }}" @selected((string) old('product_group_id') === (string) $group->id)>{{ $group->nome }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Se escolher um grupo, o filtro fica ainda mais específico para esta TV.</p>
                            <x-input-error :messages="$errors->get('product_group_id')" class="mt-2" />
                        </div>

                        @if (!($isDefaultAdmin && ($adminSemEmpresaAtiva ?? false)))
                            <div class="flex justify-end">
                                <x-primary-button>
                                    Ativar
                                </x-primary-button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">TVs cadastradas</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                @if ($isDefaultAdmin && ($showAllDevices ?? false))
                                    Exibindo dispositivos de todas as empresas.
                                @elseif (($isRevenda ?? false) && ($showAllDevices ?? false))
                                    Exibindo dispositivos de todas as empresas vinculadas a esta revenda.
                                @elseif ($isDefaultAdmin)
                                    Exibindo apenas os dispositivos da empresa ativa.
                                @else
                                    Exibindo apenas os dispositivos da sua empresa.
                                @endif
                            </p>
                        </div>

                        @if ($canShowAllDevices ?? false)
                            <form method="GET" action="{{ route('admin.activate-tv.index') }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="hidden"
                                        name="show_all_devices"
                                        value="0"
                                    >
                                    <input
                                        type="checkbox"
                                        name="show_all_devices"
                                        value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm"
                                        @checked($showAllDevices ?? false)
                                        onchange="this.form.submit()"
                                    >
                                    <span>Ver lista de todas as empresas</span>
                                </label>
                                <p class="mt-1 text-xs text-slate-500">
                                    @if ($isDefaultAdmin)
                                        Disponivel para admin principal. O padrao e desmarcado.
                                    @else
                                        Disponivel para revenda. Quando marcado, lista apenas as empresas vinculadas a esta revenda.
                                    @endif
                                </p>
                            </form>
                        @endif
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-white/80 text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">TV</th>
                                    <th class="px-4 py-3 text-left font-semibold">Empresa</th>
                                    <th class="px-4 py-3 text-left font-semibold">Configuração</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold">Última comunicação</th>
                                    <th class="px-4 py-3 text-right font-semibold">Detalhes</th>
                                </tr>
                            </thead>
                            @forelse ($devices as $device)
                                @php
                                    $isOnline = $device->last_seen_at && $device->last_seen_at->gt(now()->subMinutes(2));
                                    $statusLabel = ! $device->ativo
                                        ? 'Desativada'
                                        : ($isOnline ? 'Online' : 'Offline');
                                    $statusClass = ! $device->ativo
                                        ? 'bg-red-100 text-red-700'
                                        : ($isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700');
                                    $empresaNome = $device->empresa?->NOME ?? $device->empresa?->nome ?? null;
                                    $empresaCnpj = $device->empresa?->CNPJ_CPF ?? $device->empresa?->cnpj_cpf ?? null;
                                    $deviceModels = $modelsByEmpresa->get((int) $device->empresa_id, collect());
                                    $deviceDepartments = $departmentsByEmpresa->get((int) $device->empresa_id, collect());
                                    $deviceGroups = $groupsByEmpresa->get((int) $device->empresa_id, collect());
                                    $selectedModel = $deviceModels->firstWhere('id', (int) ($device->configuration?->web_screen_model_id ?? 0));
                                    $selectedDepartment = $deviceDepartments->firstWhere('id', (int) ($device->configuration?->product_department_id ?? 0));
                                    $selectedGroup = $deviceGroups->firstWhere('id', (int) ($device->configuration?->product_group_id ?? 0));
                                @endphp
                                <tbody x-data="{ open: false }" class="divide-y divide-slate-200 bg-white" :class="open ? 'bg-sky-50/70' : 'bg-white'">
                                    <tr class="cursor-pointer border-l-4 border-transparent transition hover:bg-slate-50" :class="open ? 'border-sky-400 bg-sky-50' : 'border-transparent'" @click="open = !open">
                                        <td class="px-4 py-3 align-top">
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-slate-900">{{ $device->nome ?: 'TV sem nome' }}</p>
                                                <p class="mt-1 truncate text-xs text-slate-500">{{ $device->local ?: 'Sem local definido' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-slate-800">{{ $empresaNome ?? 'Empresa nao vinculada' }}</p>
                                                <p class="mt-1 truncate text-xs text-slate-500">{{ $empresaCnpj ?: 'Sem documento' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="space-y-1 text-xs text-slate-600">
                                                <p><span class="font-semibold text-slate-700">Modelo:</span> {{ $selectedModel?->nome ?? 'Geral da empresa' }}</p>
                                                <p><span class="font-semibold text-slate-700">Depto:</span> {{ $selectedDepartment?->nome ?? 'Todos' }}</p>
                                                <p><span class="font-semibold text-slate-700">Grupo:</span> {{ $selectedGroup?->nome ?? 'Todos' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-slate-600">{{ $device->last_seen_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right align-top">
                                            <button type="button" class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-xs font-medium shadow-sm transition" :class="open ? 'border-sky-300 bg-sky-100 text-sky-800 hover:bg-sky-200' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100'" @click.stop="open = !open">
                                                <span x-text="open ? 'Fechar' : 'Abrir'"></span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="open" x-cloak>
                                        <td colspan="6" class="bg-sky-50/80 px-4 py-4">
                                            <div class="rounded-xl border border-sky-200 bg-white p-4 shadow-sm shadow-sky-100/60 ring-1 ring-sky-100">
                                                <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Identificação do dispositivo</p>
                                                        <input type="text" value="{{ $device->device_uuid }}" class="mt-1 w-full rounded-md border-gray-200 bg-slate-50 font-mono text-xs text-slate-700" readonly>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Token</p>
                                                        <input type="text" value="{{ $device->token }}" class="mt-1 w-full rounded-md border-gray-200 bg-slate-50 font-mono text-xs text-slate-700" readonly>
                                                    </div>
                                                </div>

                                                <form id="update-device-{{ $device->id }}" method="POST" action="{{ route('admin.activate-tv.devices.update', $device) }}" class="space-y-4">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="devices_page" value="{{ $devices->currentPage() }}">

                                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700">Nome da TV</label>
                                                            <input name="nome" type="text" value="{{ old('nome', $device->nome) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700">Local</label>
                                                            <input name="local" type="text" value="{{ old('local', $device->local) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700">Modelo em uso</label>
                                                            <select name="web_screen_model_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                                                                <option value="">Usar configuracao geral da empresa</option>
                                                                @foreach ($deviceModels as $model)
                                                                    <option value="{{ $model->id }}" @selected((string) old('web_screen_model_id', $device->configuration?->web_screen_model_id) === (string) $model->id)>{{ $model->nome }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="flex items-end">
                                                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                                                <input type="hidden" name="ativo" value="0">
                                                                <input type="checkbox" name="ativo" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" @checked($device->ativo)>
                                                                <span>Dispositivo ativo</span>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="grid gap-4 md:grid-cols-2">
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700">Departamento dos produtos</label>
                                                            <select name="product_department_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                                                                <option value="">Todos</option>
                                                                @foreach ($deviceDepartments as $department)
                                                                    <option value="{{ $department->id }}" @selected((string) old('product_department_id', $device->configuration?->product_department_id) === (string) $department->id)>{{ $department->nome }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-medium text-slate-700">Grupo dos produtos</label>
                                                            <select name="product_group_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                                                                <option value="">Todos</option>
                                                                @foreach ($deviceGroups as $group)
                                                                    <option value="{{ $group->id }}" data-department-id="{{ $group->departamento_id }}" @selected((string) old('product_group_id', $device->configuration?->product_group_id) === (string) $group->id)>{{ $group->nome }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-4">
                                                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">Salvar</button>
                                                    </div>
                                                </form>

                                                <div class="mt-3 flex justify-end border-t border-slate-200 pt-3">
                                                    <form method="POST" action="{{ route('admin.activate-tv.devices.destroy', $device) }}" class="inline" onsubmit="return confirm('Deseja remover esta TV?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="devices_page" value="{{ $devices->currentPage() }}">
                                                        <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-500">Excluir</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">Nenhuma TV cadastrada encontrada.</td>
                                    </tr>
                                </tbody>
                            @endforelse
                        </table>
                    </div>

                    <div>
                        {{ $devices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const bindGroupFilter = (departmentSelect, groupSelect) => {
                if (!(departmentSelect instanceof HTMLSelectElement) || !(groupSelect instanceof HTMLSelectElement)) {
                    return;
                }

                const applyFilter = () => {
                    const selectedDepartmentId = String(departmentSelect.value || '');
                    const currentGroupValue = String(groupSelect.value || '');
                    let hasVisibleSelectedGroup = currentGroupValue === '';

                    Array.from(groupSelect.options).forEach((option) => {
                        if (option.value === '') {
                            option.hidden = false;
                            return;
                        }

                        const optionDepartmentId = String(option.getAttribute('data-department-id') || '');
                        const shouldShow = selectedDepartmentId === '' || optionDepartmentId === selectedDepartmentId;
                        option.hidden = !shouldShow;

                        if (shouldShow && option.value === currentGroupValue) {
                            hasVisibleSelectedGroup = true;
                        }
                    });

                    if (!hasVisibleSelectedGroup) {
                        groupSelect.value = '';
                    }
                };

                departmentSelect.addEventListener('change', applyFilter);
                applyFilter();
            };

            bindGroupFilter(
                document.getElementById('product_department_id'),
                document.getElementById('product_group_id')
            );

            document.querySelectorAll('form[id^="update-device-"]').forEach((form) => {
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                const formId = String(form.id || '').trim();
                if (formId === '') {
                    return;
                }

                bindGroupFilter(
                    document.querySelector(`select[form="${formId}"][name="product_department_id"]`),
                    document.querySelector(`select[form="${formId}"][name="product_group_id"]`)
                );
            });
        });
    </script>
</x-app-layout>
