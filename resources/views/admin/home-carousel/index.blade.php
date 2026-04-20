<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Carrossel da Tela Inicial
            </h2>
            <a href="{{ route('admin.home-carousel.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                Novo Slide
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (! empty($setupError))
                        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                            <div class="font-semibold">Carrossel ainda nao inicializado</div>
                            <div class="mt-1">{{ $setupError }}</div>
                            <div class="mt-2">Use `./vendor/bin/sail artisan migrate` se o projeto estiver rodando com Sail.</div>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ordem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Imagem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Conteúdo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Origem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($slides as $slide)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $slide->sort_order }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if ($slide->resolvedImageUrl())
                                                <img src="{{ $slide->resolvedImageUrl() }}" alt="{{ $slide->title ?: 'Slide' }}" class="h-16 w-28 object-cover rounded border">
                                            @else
                                                <span class="text-gray-400">Sem imagem</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <div class="font-semibold text-gray-900">{{ $slide->title ?: 'Sem título' }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $slide->subtitle ?: 'Sem subtítulo' }}</div>
                                            @if ($slide->button_link)
                                                <div class="text-xs text-indigo-600 mt-1">{{ $slide->button_label ?: 'Link do slide' }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $slide->image_source_type === 'link' ? 'Link da internet' : 'Upload' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $slide->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $slide->is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <a href="{{ route('admin.home-carousel.edit', $slide) }}" class="text-indigo-600 hover:text-indigo-800 mr-3">Editar</a>
                                            <form method="POST" action="{{ route('admin.home-carousel.destroy', $slide) }}" class="inline" onsubmit="return confirm('Deseja remover este slide?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Nenhum slide cadastrado ainda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>