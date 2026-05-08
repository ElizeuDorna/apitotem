<div class="space-y-6" x-data="{ formEnabled: false }" x-on:downloads-upload-create.window="formEnabled = true; $wire.cancelEditing()" x-on:downloads-upload-edit.window="formEnabled = true">
    @if (($statusMessage ?? null) || session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ $statusMessage ?? session('status') }}
        </div>
    @endif

    @if ($isDefaultAdmin)
        <div id="upload-download" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">{{ $editingDownloadId ? 'Editar upload sem recarregar a página' : 'Novo upload sem recarregar a página' }}</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Piloto em Livewire para validar o fluxo de upload no admin sem refresh completo.
                    </p>
                </div>

                <div wire:loading.flex wire:target="save,file" class="items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-sky-500"></span>
                    Enviando...
                </div>
            </div>

            <form wire:submit="save" class="space-y-5">
                <div x-show="!formEnabled" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Clique em <span class="font-semibold">Novo upload</span> para habilitar os campos de nome, descricao e arquivo.
                </div>

                @if ($editingDownloadId)
                    <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        Modo edicao ativo. O arquivo e opcional; envie outro apenas se quiser substituir o atual.
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="lw-download-title" class="mb-1 block text-sm font-semibold text-slate-800">Nome para exibição</label>
                        <input
                            id="lw-download-title"
                            type="text"
                            wire:model="title"
                            x-bind:disabled="!formEnabled"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="lw-download-file-{{ $uploadIteration }}" class="mb-1 block text-sm font-semibold text-slate-800">Arquivo</label>
                        <input
                            id="lw-download-file-{{ $uploadIteration }}"
                            type="file"
                            wire:model="file"
                            x-bind:disabled="!formEnabled"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                        <p class="mt-1 text-xs text-slate-500">Tamanho máximo de 256 MB. {{ $editingDownloadId ? 'Opcional na edicao.' : '' }}</p>
                        @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="lw-download-description" class="mb-1 block text-sm font-semibold text-slate-800">Descrição</label>
                    <textarea
                        id="lw-download-description"
                        rows="4"
                        wire:model="description"
                        x-bind:disabled="!formEnabled"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    ></textarea>
                    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save,file"
                        x-bind:disabled="!formEnabled"
                        class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ $editingDownloadId ? 'Atualizar upload' : 'Salvar upload' }}
                    </button>
                    @if ($editingDownloadId)
                        <button
                            type="button"
                            wire:click="cancelEditing"
                            class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancelar edicao
                        </button>
                    @endif
                    <p class="text-xs text-slate-500">A grade abaixo atualiza automaticamente apos o envio.</p>
                </div>
            </form>
        </div>
    @endif

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
                                            <button
                                                type="button"
                                                wire:click="editDownload({{ $download->id }})"
                                                x-on:click="$dispatch('downloads-upload-edit'); document.getElementById('upload-download')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                                class="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="deleteDownload({{ $download->id }})"
                                                wire:confirm="Excluir este arquivo de download?"
                                                class="font-medium text-rose-600 hover:text-rose-800"
                                            >
                                                Excluir
                                            </button>
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