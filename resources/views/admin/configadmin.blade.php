<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Config Admin
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ session('warning') }}</div>
                    @endif

                    <div id="identidade-painel" class="rounded-lg border border-slate-200 bg-slate-50 p-4 scroll-mt-24">
                        <h3 class="mb-3 text-base font-semibold text-slate-800">Identidade do Painel</h3>

                        <form method="POST" action="{{ route('admin.configadmin.update') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            @if (!($panelBrandIconFeatureReady ?? false))
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    Recurso de icone ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                                </div>
                            @endif

                            @php($panelBrandIconPreviewUrl = old('panelBrandIconUrl', $config->panelBrandIconUrl ?? ''))

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold">Upload do icone (favicon + lateral)</label>
                                    <input
                                        type="file"
                                        name="panelBrandIconFile"
                                        accept=".png,.jpg,.jpeg,.webp,.svg,.ico,image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon"
                                        class="w-full rounded border px-3 py-2"
                                    />
                                    <p class="mt-1 text-xs text-slate-500">Formatos: PNG, JPG, WEBP, SVG ou ICO. Tamanho maximo: 2MB.</p>
                                    @error('panelBrandIconFile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                                    <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                        <input type="checkbox" name="removePanelBrandIcon" value="1" class="h-4 w-4" {{ old('removePanelBrandIcon') ? 'checked' : '' }}>
                                        <span>Remover icone personalizado e voltar para o padrao</span>
                                    </label>
                                </div>

                                <div>
                                    <p class="mb-1 text-sm font-semibold">Pre-visualizacao atual</p>
                                    <div class="flex h-24 w-24 items-center justify-center rounded-xl border bg-white shadow-sm">
                                        @if(is_string($panelBrandIconPreviewUrl) && trim($panelBrandIconPreviewUrl) !== '')
                                            <img src="{{ $panelBrandIconPreviewUrl }}" alt="Icone atual do painel" class="h-16 w-16 object-contain">
                                        @else
                                            <x-application-logo class="h-12 w-auto fill-current text-slate-700" />
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2">
                                <x-primary-button>Salvar</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
