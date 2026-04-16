<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Token da API
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($isDefaultAdmin)
                        <form method="GET" action="{{ route('admin.api-token.index') }}" class="space-y-2">
                            <label for="empresa_id" class="block text-sm font-medium text-gray-700">Selecionar empresa</label>
                            <input
                                id="empresa_search"
                                type="text"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Buscar por nome ou CNPJ/CPF"
                            >
                            <div class="flex items-center gap-3">
                                <select id="empresa_id" name="empresa_id" size="8" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($empresas as $empresaOption)
                                        <option
                                            value="{{ $empresaOption->id }}"
                                            data-index="{{ $loop->index }}"
                                            data-name="{{ mb_strtolower($empresaOption->NOME) }}"
                                            data-display-name="{{ $empresaOption->NOME }}"
                                            data-display-doc="{{ $empresaOption->CNPJ_CPF }}"
                                            data-token="{{ $empresaOption->api_token }}"
                                            data-cnpj="{{ preg_replace('/\D+/', '', (string) $empresaOption->CNPJ_CPF) }}"
                                            data-label="{{ $empresaOption->NOME }} - {{ $empresaOption->CNPJ_CPF }}"
                                            data-search="{{ mb_strtolower($empresaOption->NOME . ' ' . $empresaOption->CNPJ_CPF) }}"
                                            @selected((int) $empresaOption->id === (int) $empresa->id)
                                        >
                                            {{ $empresaOption->NOME }} - {{ $empresaOption->CNPJ_CPF }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-primary-button>
                                    Carregar
                                </x-primary-button>
                            </div>
                            <p class="text-xs text-gray-500">★ CNPJ/CPF começa com os números digitados · • CNPJ/CPF contém os números digitados</p>
                            <div class="rounded-md bg-blue-50 p-3">
                                <p class="text-xs text-blue-700">Empresa marcada no seletor</p>
                                <p id="empresa-preview-nome" class="text-sm font-medium text-blue-900">{{ $empresa->NOME }}</p>
                                <p id="empresa-preview-doc" class="text-xs text-blue-800">{{ $empresa->CNPJ_CPF }}</p>
                            </div>
                        </form>
                    @endif

                    <div>
                        <p class="text-sm text-gray-600">Empresa selecionada</p>
                        <p class="font-medium text-gray-900">{{ $empresa->NOME }}</p>
                        <p class="text-sm text-gray-500">{{ $empresa->CNPJ_CPF }}</p>
                    </div>

                    <div>
                        <label for="api_token" class="block text-sm font-medium text-gray-700">Token atual</label>
                        <textarea id="api_token" rows="3" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $token ?? 'Nenhum token gerado.' }}</textarea>
                        <p class="mt-2 text-xs text-gray-500">Use este valor no header <strong>Authorization: Bearer TOKEN_DA_EMPRESA</strong> nas rotas protegidas da API.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.api-token.regenerate') }}" class="flex items-center justify-between gap-4">
                        @csrf
                        @if ($isDefaultAdmin)
                            <input id="empresa_id_hidden" type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                        @endif
                        <p class="text-sm text-gray-600">Gerar um novo token invalida o token anterior imediatamente.</p>
                        <x-primary-button>
                            Gerar novo token
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($isDefaultAdmin)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('empresa_search');
                const select = document.getElementById('empresa_id');
                const hiddenEmpresaId = document.getElementById('empresa_id_hidden');
                const previewNome = document.getElementById('empresa-preview-nome');
                const previewDoc = document.getElementById('empresa-preview-doc');
                const tokenField = document.getElementById('api_token');

                if (!searchInput || !select) {
                    return;
                }

                function syncSelectedCompanyPreview() {
                    const selectedOption = select.selectedOptions.length > 0 ? select.selectedOptions[0] : null;

                    if (!selectedOption) {
                        return;
                    }

                    if (hiddenEmpresaId) {
                        hiddenEmpresaId.value = selectedOption.value;
                    }

                    if (previewNome) {
                        previewNome.textContent = selectedOption.dataset.displayName || selectedOption.text;
                    }

                    if (previewDoc) {
                        previewDoc.textContent = selectedOption.dataset.displayDoc || '';
                    }

                    if (tokenField) {
                        tokenField.value = selectedOption.dataset.token || 'Nenhum token gerado.';
                    }
                }

                searchInput.addEventListener('input', function () {
                    const query = searchInput.value.toLowerCase().trim();
                    const numericQuery = query.replace(/\D/g, '');
                    const options = Array.from(select.options);

                    options.forEach(function (option) {
                        const haystack = option.dataset.search || option.text.toLowerCase();
                        const cnpj = option.dataset.cnpj || '';
                        const matchesText = haystack.includes(query);
                        const matchesCnpj = numericQuery !== '' && cnpj.includes(numericQuery);
                        const startsWithCnpj = numericQuery !== '' && cnpj.startsWith(numericQuery);

                        const originalLabel = option.dataset.label || option.text;
                        option.text = originalLabel;

                        if (numericQuery !== '' && startsWithCnpj) {
                            option.text = `★ ${originalLabel}`;
                        } else if (numericQuery !== '' && matchesCnpj) {
                            option.text = `• ${originalLabel}`;
                        }

                        option.hidden = query !== '' && !matchesText && !matchesCnpj;
                    });

                    const rankedOptions = options.sort(function (left, right) {
                        const leftIndex = Number(left.dataset.index || 0);
                        const rightIndex = Number(right.dataset.index || 0);

                        if (left.hidden !== right.hidden) {
                            return left.hidden ? 1 : -1;
                        }

                        if (numericQuery === '') {
                            return leftIndex - rightIndex;
                        }

                        const leftCnpj = left.dataset.cnpj || '';
                        const rightCnpj = right.dataset.cnpj || '';

                        const leftStarts = leftCnpj.startsWith(numericQuery);
                        const rightStarts = rightCnpj.startsWith(numericQuery);

                        if (leftStarts !== rightStarts) {
                            return leftStarts ? -1 : 1;
                        }

                        const leftIncludes = leftCnpj.includes(numericQuery);
                        const rightIncludes = rightCnpj.includes(numericQuery);

                        if (leftIncludes !== rightIncludes) {
                            return leftIncludes ? -1 : 1;
                        }

                        return leftIndex - rightIndex;
                    });

                    rankedOptions.forEach(function (option) {
                        select.appendChild(option);
                    });

                    const selectedVisible = select.selectedOptions.length > 0 && !select.selectedOptions[0].hidden;

                    if (!selectedVisible) {
                        const firstVisible = options.find(function (option) {
                            return !option.hidden;
                        });

                        if (firstVisible) {
                            firstVisible.selected = true;
                        }
                    }

                    syncSelectedCompanyPreview();
                });

                select.addEventListener('change', syncSelectedCompanyPreview);
                syncSelectedCompanyPreview();
            });
        </script>
    @endif
</x-app-layout>
