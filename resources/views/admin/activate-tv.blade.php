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

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Nome</th>
                                    <th class="px-3 py-2 text-left">Local</th>
                                    <th class="px-3 py-2 text-left">Identificacao do Dispositivo</th>
                                    <th class="px-3 py-2 text-left">Token</th>
                                    <th class="px-3 py-2 text-left">Empresa</th>
                                    <th class="px-3 py-2 text-left">Modelo em uso</th>
                                    <th class="px-3 py-2 text-left">Departamento</th>
                                    <th class="px-3 py-2 text-left">Grupo</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-left">Última comunicação</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($devices as $device)
                                    @php
                                        $isOnline = $device->last_seen_at && $device->last_seen_at->gt(now()->subMinutes(2));
                                        $statusLabel = ! $device->ativo
                                            ? 'Desativada'
                                            : ($isOnline ? 'Online' : 'Offline');
                                        $statusClass = ! $device->ativo
                                            ? 'text-red-700'
                                            : ($isOnline ? 'text-green-700' : 'text-gray-600');
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">
                                            <input form="update-device-{{ $device->id }}" name="nome" type="text" value="{{ old('nome', $device->nome) }}" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input form="update-device-{{ $device->id }}" name="local" type="text" value="{{ old('local', $device->local) }}" class="w-full rounded-md border-gray-300 shadow-sm">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" value="{{ $device->device_uuid }}" class="w-full rounded-md border-gray-200 bg-gray-50 font-mono text-xs text-gray-700" readonly>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" value="{{ $device->token }}" class="w-full rounded-md border-gray-200 bg-gray-50 font-mono text-xs text-gray-700" readonly>
                                        </td>
                                        <td class="px-3 py-2">
                                            @php
                                                $empresaNome = $device->empresa?->NOME ?? $device->empresa?->nome ?? null;
                                                $empresaCnpj = $device->empresa?->CNPJ_CPF ?? $device->empresa?->cnpj_cpf ?? null;
                                                $deviceModels = $modelsByEmpresa->get((int) $device->empresa_id, collect());
                                                $deviceDepartments = $departmentsByEmpresa->get((int) $device->empresa_id, collect());
                                                $deviceGroups = $groupsByEmpresa->get((int) $device->empresa_id, collect());
                                            @endphp
                                            <span>
                                                {{ $empresaNome ?? 'Empresa nao vinculada' }}
                                                @if ($empresaCnpj)
                                                    - {{ $empresaCnpj }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select form="update-device-{{ $device->id }}" name="web_screen_model_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                                <option value="">Usar configuracao geral da empresa</option>
                                                @foreach ($deviceModels as $model)
                                                    <option value="{{ $model->id }}" @selected((string) old('web_screen_model_id', $device->configuration?->web_screen_model_id) === (string) $model->id)>{{ $model->nome }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select form="update-device-{{ $device->id }}" name="product_department_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                                <option value="">Todos</option>
                                                @foreach ($deviceDepartments as $department)
                                                    <option value="{{ $department->id }}" @selected((string) old('product_department_id', $device->configuration?->product_department_id) === (string) $department->id)>{{ $department->nome }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <select form="update-device-{{ $device->id }}" name="product_group_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                                <option value="">Todos</option>
                                                @foreach ($deviceGroups as $group)
                                                    <option value="{{ $group->id }}" data-department-id="{{ $group->departamento_id }}" @selected((string) old('product_group_id', $device->configuration?->product_group_id) === (string) $group->id)>{{ $group->nome }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <label class="inline-flex items-center gap-2">
                                                <input form="update-device-{{ $device->id }}" type="hidden" name="ativo" value="0">
                                                <input form="update-device-{{ $device->id }}" type="checkbox" name="ativo" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" @checked($device->ativo)>
                                                <span class="text-xs {{ $statusClass }}">{{ $statusLabel }}</span>
                                            </label>
                                        </td>
                                        <td class="px-3 py-2">{{ $device->last_seen_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <form id="update-device-{{ $device->id }}" method="POST" action="{{ route('admin.activate-tv.devices.update', $device) }}" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="devices_page" value="{{ $devices->currentPage() }}">
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">Salvar</button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.activate-tv.devices.destroy', $device) }}" class="inline" onsubmit="return confirm('Deseja remover esta TV?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="devices_page" value="{{ $devices->currentPage() }}">
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-500">Excluir</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="px-3 py-8 text-center text-gray-500">Nenhuma TV cadastrada encontrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
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

                bindGroupFilter(
                    form.querySelector('select[name="product_department_id"]'),
                    form.querySelector('select[name="product_group_id"]')
                );
            });
        });
    </script>
</x-app-layout>
