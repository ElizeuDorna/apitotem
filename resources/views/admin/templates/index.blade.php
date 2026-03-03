<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editor de Template</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

                    <div class="mb-4 text-right">
                        <a href="{{ route('admin.templates.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm hover:bg-indigo-700">Novo Template</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Nome</th>
                                    <th class="px-3 py-2 text-left">Layout</th>
                                    <th class="px-3 py-2 text-left">Blocos</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($templates as $template)
                                    <tr>
                                        <td class="px-3 py-2">{{ $template->nome }}</td>
                                        <td class="px-3 py-2">{{ $template->tipo_layout }}</td>
                                        <td class="px-3 py-2">{{ $template->items_count }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('admin.templates.edit', $template) }}" class="text-indigo-600 hover:text-indigo-800">Editar</a>
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
</x-app-layout>
