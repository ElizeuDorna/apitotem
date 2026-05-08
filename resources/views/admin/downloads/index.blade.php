<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Downloads
            </h2>

            @if ($isDefaultAdmin)
                <button
                    type="button"
                    x-data="{}"
                    x-on:click="$dispatch('downloads-upload-create'); document.getElementById('upload-download')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                    class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Novo upload
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
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

            <livewire:admin.downloads-upload-panel />
        </div>
    </div>
</x-app-layout>