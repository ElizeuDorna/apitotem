<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Organizar Lista
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.organizar-lista.update') }}" class="space-y-6">
                        @csrf

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3">
                            <h3 class="text-base font-semibold text-gray-800">Modo de organização da lista</h3>

                            <label class="inline-flex items-center gap-2 mr-6">
                                <input
                                    type="radio"
                                    name="productListOrderMode"
                                    value="grupo"
                                    class="text-indigo-600 border-gray-300"
                                    @checked(old('productListOrderMode', $config->productListOrderMode ?? 'grupo') === 'grupo')
                                >
                                <span class="text-sm text-gray-700">Organizar por grupo</span>
                            </label>

                            <label class="inline-flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="productListOrderMode"
                                    value="departamento"
                                    class="text-indigo-600 border-gray-300"
                                    @checked(old('productListOrderMode', $config->productListOrderMode ?? 'grupo') === 'departamento')
                                >
                                <span class="text-sm text-gray-700">Organizar por departamento</span>
                            </label>

                            @error('productListOrderMode')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

                            <div class="pt-2">
                                <p class="text-sm font-medium text-gray-700 mb-2">Ordem alfabética dos produtos (dentro de cada grupo/departamento)</p>
                                <label class="inline-flex items-center gap-2 mr-6">
                                    <input
                                        type="radio"
                                        name="productAlphabeticalDirection"
                                        value="asc"
                                        class="text-indigo-600 border-gray-300"
                                        @checked(old('productAlphabeticalDirection', $config->productAlphabeticalDirection ?? 'asc') === 'asc')
                                    >
                                    <span class="text-sm text-gray-700">A → Z</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="radio"
                                        name="productAlphabeticalDirection"
                                        value="desc"
                                        class="text-indigo-600 border-gray-300"
                                        @checked(old('productAlphabeticalDirection', $config->productAlphabeticalDirection ?? 'asc') === 'desc')
                                    >
                                    <span class="text-sm text-gray-700">Z → A</span>
                                </label>
                                @error('productAlphabeticalDirection')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>

                            <p class="text-xs text-gray-500">
                                Por grupo: segue primeiro a ordem dos grupos abaixo. Por departamento: segue a ordem dos departamentos e, dentro de cada departamento, a ordem dos grupos abaixo.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                                <h3 class="text-base font-semibold text-gray-800 mb-3">Sequência dos Departamentos</h3>
                                <p class="text-xs text-gray-500 mb-3">Use os botões ↑ e ↓ para mudar a ordem.</p>

                                <input type="hidden" name="productDepartmentOrder" id="productDepartmentOrder" value='@json(old('productDepartmentOrder', json_encode(($config->productDepartmentOrder ?? []))))'>

                                <div id="departmentList" class="space-y-2">
                                    @forelse ($departamentos as $departamento)
                                        <div class="flex items-center justify-between rounded border border-gray-300 bg-white px-3 py-2" data-id="{{ $departamento->id }}">
                                            <span class="text-sm text-gray-800">{{ $departamento->nome }}</span>
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="move-up rounded border px-2 py-1 text-xs">↑</button>
                                                <button type="button" class="move-down rounded border px-2 py-1 text-xs">↓</button>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500">Nenhum departamento cadastrado.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                                <h3 class="text-base font-semibold text-gray-800 mb-3">Sequência dos Grupos</h3>
                                <p class="text-xs text-gray-500 mb-3">Use os botões ↑ e ↓ para mudar a ordem.</p>

                                <input type="hidden" name="productGroupOrder" id="productGroupOrder" value='@json(old('productGroupOrder', json_encode(($config->productGroupOrder ?? []))))'>

                                <div id="groupList" class="space-y-2 max-h-[420px] overflow-auto pr-1">
                                    @forelse ($grupos as $grupo)
                                        <div class="flex items-center justify-between rounded border border-gray-300 bg-white px-3 py-2" data-id="{{ $grupo->id }}">
                                            <span class="text-sm text-gray-800">{{ $grupo->nome }} <span class="text-gray-400">({{ $grupo->departamento->nome ?? 'Sem departamento' }})</span></span>
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="move-up rounded border px-2 py-1 text-xs">↑</button>
                                                <button type="button" class="move-down rounded border px-2 py-1 text-xs">↓</button>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500">Nenhum grupo cadastrado.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>Salvar Organização</x-primary-button>
                            <a href="{{ route('tv.totemweb') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Abrir Totem Web</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bindSortableList(listId, inputId) {
            const list = document.getElementById(listId);
            const hiddenInput = document.getElementById(inputId);

            if (!list || !hiddenInput) {
                return;
            }

            const saveOrder = () => {
                const order = Array.from(list.querySelectorAll('[data-id]'))
                    .map((item) => Number(item.getAttribute('data-id')))
                    .filter((id) => Number.isFinite(id) && id > 0);

                hiddenInput.value = JSON.stringify(order);
            };

            list.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const row = target.closest('[data-id]');
                if (!row) {
                    return;
                }

                if (target.classList.contains('move-up')) {
                    const prev = row.previousElementSibling;
                    if (prev && prev.hasAttribute('data-id')) {
                        row.parentNode.insertBefore(row, prev);
                        saveOrder();
                    }
                }

                if (target.classList.contains('move-down')) {
                    const next = row.nextElementSibling;
                    if (next && next.hasAttribute('data-id')) {
                        row.parentNode.insertBefore(next, row);
                        saveOrder();
                    }
                }
            });

            saveOrder();
        }

        bindSortableList('departmentList', 'productDepartmentOrder');
        bindSortableList('groupList', 'productGroupOrder');
    </script>
</x-app-layout>
