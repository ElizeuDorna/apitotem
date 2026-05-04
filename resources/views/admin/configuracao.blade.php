@extends('home')

@section('title', 'Administração - Config Android')

@section('content')
<div class="mb-4 px-2 sm:px-4">
    <x-back-button />
</div>

<div class="max-w-4xl mx-auto bg-white p-4 sm:p-6 md:p-8 shadow rounded-lg">
    <h2 class="text-xl sm:text-2xl font-bold mb-5 sm:mb-6">Config Android</h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ url('/admin/configuracao') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">API URL</label>
                <input type="text" name="apiUrl" value="{{ old('apiUrl', $config->apiUrl) }}" class="w-full border rounded px-3 py-2" />
                @error('apiUrl')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Intervalo de atualização (s)</label>
                <input type="number" name="apiRefreshInterval" value="{{ old('apiRefreshInterval', $config->apiRefreshInterval) }}" class="w-full border rounded px-3 py-2" />
                @error('apiRefreshInterval')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Cor do preço</label>
                <input type="color" name="priceColor" value="{{ old('priceColor', $config->priceColor) }}" class="w-full h-10 border rounded px-1" />
                @error('priceColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Cor da oferta</label>
                <input type="color" name="offerColor" value="{{ old('offerColor', $config->offerColor) }}" class="w-full h-10 border rounded px-1" />
                @error('offerColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Cor de fundo da linha</label>
                <input type="color" name="rowBackgroundColor" value="{{ old('rowBackgroundColor', $config->rowBackgroundColor) }}" class="w-full h-10 border rounded px-1" />
                @error('rowBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Cor da borda</label>
                <input type="color" name="borderColor" value="{{ old('borderColor', $config->borderColor) }}" class="w-full h-10 border rounded px-1" />
                @error('borderColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Cor de fundo da app</label>
                <input type="color" name="appBackgroundColor" value="{{ old('appBackgroundColor', $config->appBackgroundColor) }}" class="w-full h-10 border rounded px-1" />
                @error('appBackgroundColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Cor da borda principal</label>
                <input type="color" name="mainBorderColor" value="{{ old('mainBorderColor', $config->mainBorderColor) }}" class="w-full h-10 border rounded px-1" />
                @error('mainBorderColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-3 font-semibold text-sm sm:text-base">
                    <input type="checkbox" name="useGradient" value="1" class="h-4 w-4" {{ old('useGradient', $config->useGradient) ? 'checked' : '' }} />
                    <span>Usar gradiente?</span>
                </label>
                @error('useGradient')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Start color</label>
                <input type="color" name="gradientStartColor" value="{{ old('gradientStartColor', $config->gradientStartColor) }}" class="w-full h-10 border rounded px-1" />
                @error('gradientStartColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">End color</label>
                <input type="color" name="gradientEndColor" value="{{ old('gradientEndColor', $config->gradientEndColor) }}" class="w-full h-10 border rounded px-1" />
                @error('gradientEndColor')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Stop 1 (0‑1)</label>
                <input type="number" step="0.01" min="0" max="1" name="gradientStop1" value="{{ old('gradientStop1', $config->gradientStop1) }}" class="w-full border rounded px-3 py-2" />
                @error('gradientStop1')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Stop 2 (0‑1)</label>
                <input type="number" step="0.01" min="0" max="1" name="gradientStop2" value="{{ old('gradientStop2', $config->gradientStop2) }}" class="w-full border rounded px-3 py-2" />
                @error('gradientStop2')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-3 font-semibold text-sm sm:text-base">
                    <input type="checkbox" name="showBorder" value="1" class="h-4 w-4" {{ old('showBorder', $config->showBorder) ? 'checked' : '' }} />
                    <span>Mostrar borda?</span>
                </label>
                @error('showBorder')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-3 font-semibold text-sm sm:text-base">
                    <input type="checkbox" name="isMainBorderEnabled" value="1" class="h-4 w-4" {{ old('isMainBorderEnabled', $config->isMainBorderEnabled) ? 'checked' : '' }} />
                    <span>Borda principal habilitada?</span>
                </label>
                @error('isMainBorderEnabled')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-3 font-semibold text-sm sm:text-base">
                    <input type="checkbox" name="showImage" value="1" class="h-4 w-4" {{ old('showImage', $config->showImage) ? 'checked' : '' }} />
                    <span>Mostrar imagem?</span>
                </label>
                @error('showImage')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Tamanho da imagem (px)</label>
                <input type="number" name="imageSize" min="16" max="512" value="{{ old('imageSize', $config->imageSize) }}" class="w-full border rounded px-3 py-2" />
                @error('imageSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-3 font-semibold text-sm sm:text-base">
                    <input type="checkbox" name="isPaginationEnabled" value="1" class="h-4 w-4" {{ old('isPaginationEnabled', $config->isPaginationEnabled) ? 'checked' : '' }} />
                    <span>Paginação habilitada?</span>
                </label>
                @error('isPaginationEnabled')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Itens por página</label>
                <input type="number" name="pageSize" min="1" max="100" value="{{ old('pageSize', $config->pageSize) }}" class="w-full border rounded px-3 py-2" />
                @error('pageSize')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-sm sm:text-base mb-1">Intervalo de paginação (s)</label>
                <input type="number" name="paginationInterval" min="1" max="60" value="{{ old('paginationInterval', $config->paginationInterval) }}" class="w-full border rounded px-3 py-2" />
                @error('paginationInterval')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        @if(auth()->user()?->isDefaultAdmin() || auth()->user()?->hasMenuAccess('configuracao'))
            <div id="identidade-painel" class="rounded-lg border border-slate-200 bg-slate-50 p-4 scroll-mt-24">
                <h3 class="mb-3 text-base font-semibold text-slate-800">Identidade do Painel</h3>

                @php($panelBrandIconPreviewUrl = old('panelBrandIconUrl', $config->panelBrandIconUrl ?? ''))

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Upload do ícone (favicon + lateral)</label>
                        <input
                            type="file"
                            name="panelBrandIconFile"
                            accept=".png,.jpg,.jpeg,.webp,.svg,.ico,image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon"
                            class="w-full rounded border px-3 py-2"
                        />
                        <p class="mt-1 text-xs text-slate-500">Formatos: PNG, JPG, WEBP, SVG ou ICO. Tamanho máximo: 2MB.</p>
                        @error('panelBrandIconFile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                        <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="removePanelBrandIcon" value="1" class="h-4 w-4" {{ old('removePanelBrandIcon') ? 'checked' : '' }}>
                            <span>Remover ícone personalizado e voltar para o padrão</span>
                        </label>
                    </div>

                    <div>
                        <p class="mb-1 text-sm font-semibold">Pré-visualização atual</p>
                        <div class="flex h-24 w-24 items-center justify-center rounded-xl border bg-white shadow-sm">
                            @if(is_string($panelBrandIconPreviewUrl) && trim($panelBrandIconPreviewUrl) !== '')
                                <img src="{{ $panelBrandIconPreviewUrl }}" alt="Ícone atual do painel" class="h-16 w-16 object-contain">
                            @else
                                <x-application-logo class="h-12 w-auto fill-current text-slate-700" />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="pt-2">
            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 text-white rounded">Salvar configurações</button>
        </div>
    </form>
</div>
@endsection
