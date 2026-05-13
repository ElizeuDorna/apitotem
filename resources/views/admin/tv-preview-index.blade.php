<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                TV Preview do Admin
            </h2>
            <a href="{{ route('admin.web-screen-config.edit', array_filter(['model_id' => $selectedModelId ?? null], static fn ($value) => $value !== null && $value !== '')) }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                Voltar para Configuração
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-5">
                    <div class="rounded-md border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                        Esta área é somente para admins testarem modelos de configuração antes de usar em TVs reais.
                    </div>

                    @if ($selectedModel)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-emerald-200 bg-emerald-50 p-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Modelo selecionado</p>
                                <p class="text-lg font-semibold text-emerald-950">{{ $selectedModel->nome }}</p>
                            </div>
                            <a href="{{ route('admin.tvpreview.show', ['model_id' => $selectedModel->id]) }}" class="inline-flex items-center rounded-md border border-emerald-600 bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                                Abrir TV Preview
                            </a>
                        </div>
                    @else
                        <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Selecione um modelo abaixo para abrir a TV Preview.
                        </div>
                    @endif

                    <div class="grid gap-4 lg:grid-cols-2 lg:items-start">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-slate-800">Modelos da empresa ativa</h3>
                                <span class="text-xs text-slate-500">Editáveis</span>
                            </div>
                            <div class="space-y-2 max-h-[32rem] overflow-y-auto pr-1">
                                @forelse($ownedModels as $model)
                                    @php($isSelected = (int) ($selectedModelId ?? 0) === (int) $model->id)
                                    <a href="{{ route('admin.tvpreview.index', ['model_id' => $model->id]) }}" class="flex items-start justify-between gap-3 rounded-md border px-3 py-3 transition {{ $isSelected ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-white hover:border-indigo-300 hover:bg-slate-50' }}">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold {{ $isSelected ? 'text-emerald-900' : 'text-slate-800' }}">{{ $model->nome }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-800">Empresa</span>
                                                @if(!empty($model->source_model_id))
                                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 font-semibold text-sky-800">Clone</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($isSelected)
                                            <span class="inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-[11px] font-semibold text-white">Ativo</span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="rounded-md border border-dashed border-slate-300 bg-white px-3 py-4 text-sm text-slate-500">
                                        Nenhum modelo próprio cadastrado ainda.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-slate-800">Modelos padrão do admin</h3>
                                <span class="text-xs text-slate-500">Para testar</span>
                            </div>
                            <div class="space-y-2 max-h-[32rem] overflow-y-auto pr-1">
                                @forelse($sharedDefaultModels as $model)
                                    @php($isSelected = (int) ($selectedModelId ?? 0) === (int) $model->id)
                                    <a href="{{ route('admin.tvpreview.index', ['model_id' => $model->id]) }}" class="flex items-start justify-between gap-3 rounded-md border px-3 py-3 transition {{ $isSelected ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-white hover:border-indigo-300 hover:bg-slate-50' }}">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold {{ $isSelected ? 'text-emerald-900' : 'text-slate-800' }}">{{ $model->nome }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 font-semibold text-indigo-800">Padrão do admin</span>
                                            </div>
                                        </div>
                                        @if($isSelected)
                                            <span class="inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-[11px] font-semibold text-white">Ativo</span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="rounded-md border border-dashed border-slate-300 bg-white px-3 py-4 text-sm text-slate-500">
                                        Nenhum modelo do admin disponível no momento.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
