<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Slides da Frente Publica</h2>
                @if ($empresa)
                    <p class="mt-1 text-sm text-gray-500">Revenda ativa: {{ $empresa->nome }}</p>
                @endif
            </div>
            @if ($empresa && empty($setupError))
                <a href="{{ route('admin.revenda-public-page-slides.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Novo Slide</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if ($setupError)
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">{{ $setupError }}</div>
            @endif

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.revenda-public-page.edit') }}" class="text-sm text-indigo-700 hover:text-indigo-900">Voltar para conteudo da frente publica</a>
                @if ($empresa && $empresa->public_page_slug)
                    <a href="{{ route('revenda.site.home', ['slug' => $empresa->public_page_slug]) }}" target="_blank" class="text-sm text-sky-700 hover:text-sky-900">Abrir link publico</a>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ordem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Imagem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Conteudo</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acoes</th>
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
                                            <div class="font-semibold text-gray-900">{{ $slide->title ?: 'Sem titulo' }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $slide->subtitle ?: 'Sem subtitulo' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $slide->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $slide->is_active ? 'Ativo' : 'Inativo' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right">
                                            <a href="{{ route('admin.revenda-public-page-slides.edit', $slide) }}" class="text-indigo-600 hover:text-indigo-800 mr-3">Editar</a>
                                            <form method="POST" action="{{ route('admin.revenda-public-page-slides.destroy', $slide) }}" class="inline" onsubmit="return confirm('Deseja remover este slide?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Nenhum slide cadastrado para esta revenda.</td>
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