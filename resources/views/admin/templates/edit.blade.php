<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Template</h2>
    </x-slot>

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
                            <select name="tipo_layout" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach($layouts as $layout)
                                    <option value="{{ $layout }}" @selected(old('tipo_layout', $template->tipo_layout) === $layout)>{{ $layout }}</option>
                                @endforeach
                            </select>
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
</x-app-layout>
