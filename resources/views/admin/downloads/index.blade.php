<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Downloads
            </h2>

            @if ($isDefaultAdmin)
                <a href="{{ route('admin.downloads.create') }}" class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Novo arquivo
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-sky-100 bg-sky-50/80 p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Links públicos</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Esta tela reúne todos os arquivos liberados para download. Os links públicos também podem ser acessados sem login em
                    <a href="{{ route('downloads.public.index') }}" target="_blank" class="font-semibold text-indigo-700 hover:text-indigo-900">{{ route('downloads.public.index') }}</a>.
                </p>
                @if (! $isDefaultAdmin)
                    <p class="mt-2 text-sm text-slate-600">Seu acesso nesta área é somente leitura.</p>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Arquivo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tamanho</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Atualizado</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Link público</th>
                                    @if ($isDefaultAdmin)
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ações</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse ($downloads as $download)
                                    <tr>
                                        <td class="px-4 py-3 align-top text-sm text-slate-700">
                                            <div class="font-semibold text-slate-900">{{ $download->title }}</div>
                                            @if ($download->description)
                                                <p class="mt-1 max-w-md text-xs text-slate-500">{{ $download->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-sm text-slate-700">{{ $download->original_name }}</td>
                                        <td class="px-4 py-3 align-top text-sm text-slate-700">{{ number_format($download->size_bytes / 1048576, 2, ',', '.') }} MB</td>
                                        <td class="px-4 py-3 align-top text-sm text-slate-700">{{ $download->updated_at?->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 align-top text-sm text-slate-700">
                                            <div class="space-y-2">
                                                <a href="{{ route('admin.downloads.download', $download) }}" class="font-medium text-indigo-700 hover:text-indigo-900">Baixar no painel</a>
                                                <div class="break-all text-xs text-slate-500">{{ route('downloads.file', $download) }}</div>
                                            </div>
                                        </td>
                                        @if ($isDefaultAdmin)
                                            <td class="px-4 py-3 align-top text-sm text-slate-700">
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <a href="{{ route('admin.downloads.edit', $download) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Editar</a>
                                                    <form method="POST" action="{{ route('admin.downloads.destroy', $download) }}" onsubmit="return confirm('Excluir este arquivo de download?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="font-medium text-rose-600 hover:text-rose-800">Excluir</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isDefaultAdmin ? 6 : 5 }}" class="px-4 py-6 text-center text-sm text-slate-500">
                                            Nenhum arquivo de download foi cadastrado ainda.
                                        </td>
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