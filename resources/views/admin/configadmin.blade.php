<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Config Admin
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50 to-cyan-50 shadow-[0_26px_80px_rgba(15,23,42,0.10)]">
                <div class="p-6 text-gray-900 md:p-8">
                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ session('warning') }}</div>
                    @endif

                    <div class="mb-6 rounded-3xl border border-slate-200/70 bg-gradient-to-r from-slate-900 via-slate-800 to-cyan-900 p-6 text-white">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-100/80">Painel Administrativo</p>
                                <h3 class="mt-2 text-2xl font-semibold text-white">Organize as configuracoes por area</h3>
                                <p class="mt-2 text-sm text-slate-200">
                                    Cada bloco agora fica separado por contexto, com atalhos no topo para facilitar navegacao e manutencao.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <a href="#tema-painel" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/15">Tema</a>
                                @if (auth()->user()?->isDefaultAdmin())
                                    <a href="#upload-apk" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/15">APK</a>
                                @endif
                                <a href="#identidade-painel" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/15">Identidade</a>
                                <a href="#integracao-asaas" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/15">Asaas</a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8 grid grid-cols-1 gap-4 lg:grid-cols-[260px_1fr]">
                        <aside class="h-fit rounded-3xl border border-slate-200/80 bg-white/80 p-5 shadow-sm backdrop-blur-sm lg:sticky lg:top-24">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Menu da Tela</p>
                            <nav class="mt-4 space-y-2 text-sm">
                                <a href="#tema-painel" class="flex items-center justify-between rounded-2xl border border-cyan-100 bg-cyan-50 px-4 py-3 font-medium text-cyan-900 transition hover:bg-cyan-100">
                                    <span>Tema do painel</span>
                                    <span class="text-xs text-cyan-700">01</span>
                                </a>
                                @if (auth()->user()?->isDefaultAdmin())
                                    <a href="#upload-apk" class="flex items-center justify-between rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 font-medium text-sky-900 transition hover:bg-sky-100">
                                        <span>Upload APK</span>
                                        <span class="text-xs text-sky-700">02</span>
                                    </a>
                                @endif
                                <a href="#identidade-painel" class="flex items-center justify-between rounded-2xl border border-violet-100 bg-violet-50 px-4 py-3 font-medium text-violet-900 transition hover:bg-violet-100">
                                    <span>Identidade visual</span>
                                    <span class="text-xs text-violet-700">03</span>
                                </a>
                                <a href="#integracao-asaas" class="flex items-center justify-between rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 font-medium text-emerald-900 transition hover:bg-emerald-100">
                                    <span>Integracao Asaas</span>
                                    <span class="text-xs text-emerald-700">04</span>
                                </a>
                            </nav>
                        </aside>

                        <div class="space-y-6">

                    <div id="tema-painel" class="scroll-mt-24 rounded-3xl border border-cyan-200/80 bg-gradient-to-br from-cyan-50 via-white to-sky-100 p-6 shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-600">Bloco 01</p>
                                <h3 class="text-lg font-semibold text-slate-900">Tema do Painel</h3>
                            </div>
                            <span class="inline-flex w-fit rounded-full border border-cyan-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700">Aparencia</span>
                        </div>
                        <p class="mb-4 text-sm text-slate-600">Alterne entre tema claro e escuro do painel administrativo.</p>
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-between rounded-2xl border border-cyan-200 bg-white/90 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-white"
                            @click="togglePanelTheme()"
                        >
                            <span x-text="panelTheme === 'dark' ? 'Tema escuro ativo' : 'Tema claro ativo'"></span>
                            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs uppercase tracking-[0.12em] text-cyan-800" x-text="panelTheme === 'dark' ? 'Dark' : 'Light'"></span>
                        </button>
                    </div>

                    @if (auth()->user()?->isDefaultAdmin())
                    <div id="upload-apk" class="scroll-mt-24 rounded-3xl border border-sky-200/80 bg-gradient-to-br from-sky-50 via-white to-blue-100 p-6 shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Bloco 02</p>
                                <h3 class="text-lg font-semibold text-slate-900">Upload APK Android</h3>
                            </div>
                            <span class="inline-flex w-fit rounded-full border border-sky-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700">Distribuicao</span>
                        </div>

                        <div class="mb-4 rounded-2xl border border-sky-100 bg-white/70 p-4 backdrop-blur-sm">
                            <p class="text-sm text-slate-600">
                                Envie o APK da WebView Android. O sistema publica sempre com o nome fixo <strong>install.apk</strong>.
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                Download público: <a href="{{ $apkDownloadUrl }}" target="_blank" class="font-semibold text-indigo-700 hover:text-indigo-900">{{ $apkDownloadUrl }}</a>
                            </p>
                        </div>

                        <div class="mb-4 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm">
                            <h4 class="text-sm font-semibold text-slate-800 mb-2">Arquivo atual</h4>
                            @if ($apkExists)
                                <div class="space-y-1 text-sm text-slate-700">
                                    <p><strong>Nome:</strong> install.apk</p>
                                    <p><strong>Tamanho:</strong> {{ number_format(($apkSizeBytes ?? 0) / 1048576, 2, ',', '.') }} MB</p>
                                    <p><strong>Atualizado em:</strong> {{ $apkLastModified ? date('d/m/Y H:i', $apkLastModified) : '-' }}</p>
                                </div>
                            @else
                                <p class="text-sm text-amber-700">Nenhum APK foi publicado ainda.</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.apk-upload.store') }}" enctype="multipart/form-data" class="space-y-3 rounded-2xl border border-white/70 bg-white/80 p-4 backdrop-blur-sm">
                            @csrf
                            <div>
                                <label for="apk_file" class="block text-sm font-medium text-gray-700 mb-1">Enviar novo APK</label>
                                <input id="apk_file" name="apk_file" type="file" accept=".apk,application/vnd.android.package-archive" class="w-full border rounded px-3 py-2 bg-white text-sm">
                                <p class="mt-1 text-xs text-gray-500">Máximo 256 MB. Será salvo como install.apk.</p>
                                @error('apk_file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Enviar APK
                                </button>
                                <a href="{{ $apkDownloadUrl }}" target="_blank" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">
                                    Testar download público
                                </a>
                            </div>
                        </form>
                    </div>
                    @endif

                    <div id="identidade-painel" class="scroll-mt-24 rounded-3xl border border-violet-200/80 bg-gradient-to-br from-violet-50 via-white to-fuchsia-100 p-6 shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-600">Bloco 03</p>
                                <h3 class="text-lg font-semibold text-slate-900">Identidade do Painel</h3>
                            </div>
                            <span class="inline-flex w-fit rounded-full border border-violet-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-violet-700">Branding</span>
                        </div>

                        <form method="POST" action="{{ route('admin.configadmin.update') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            @if (!($panelBrandIconFeatureReady ?? false))
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    Recurso de icone ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                                </div>
                            @endif

                            @if (!($panelSidebarFontFeatureReady ?? false))
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    Recurso de fonte da lateral ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                                </div>
                            @endif

                            @php($panelSidebarFontFamily = old('panelSidebarFontFamily', $config->panelSidebarFontFamily ?? ''))
                            @php($panelSidebarFontSize = old('panelSidebarFontSize', $config->panelSidebarFontSize ?? '11'))

                            <div id="fonte-lateral" class="rounded-2xl border border-violet-100 bg-white/85 p-5 shadow-sm">
                                <h4 class="mb-3 text-sm font-semibold text-slate-800">Fonte da lateral esquerda</h4>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="panelSidebarFontFamily" class="mb-1 block text-sm font-semibold">Familia da fonte</label>
                                        <select id="panelSidebarFontFamily" name="panelSidebarFontFamily" class="w-full rounded border px-3 py-2 text-sm">
                                            <option value="">Padrao do sistema</option>
                                            <option value="figtree" @selected($panelSidebarFontFamily === 'figtree')>Figtree</option>
                                            <option value="inter" @selected($panelSidebarFontFamily === 'inter')>Inter</option>
                                            <option value="roboto" @selected($panelSidebarFontFamily === 'roboto')>Roboto</option>
                                            <option value="lato" @selected($panelSidebarFontFamily === 'lato')>Lato</option>
                                            <option value="montserrat" @selected($panelSidebarFontFamily === 'montserrat')>Montserrat</option>
                                            <option value="poppins" @selected($panelSidebarFontFamily === 'poppins')>Poppins</option>
                                            <option value="open-sans" @selected($panelSidebarFontFamily === 'open-sans')>Open Sans</option>
                                            <option value="source-sans-pro" @selected($panelSidebarFontFamily === 'source-sans-pro')>Source Sans Pro</option>
                                            <option value="system-ui" @selected($panelSidebarFontFamily === 'system-ui')>System UI</option>
                                        </select>
                                        @error('panelSidebarFontFamily')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>

                                    <div>
                                        <label for="panelSidebarFontSize" class="mb-1 block text-sm font-semibold">Tamanho da fonte (px)</label>
                                        <input
                                            id="panelSidebarFontSize"
                                            type="number"
                                            name="panelSidebarFontSize"
                                            min="10"
                                            max="20"
                                            step="0.5"
                                            value="{{ $panelSidebarFontSize }}"
                                            class="w-full rounded border px-3 py-2 text-sm"
                                        />
                                        <p class="mt-1 text-xs text-slate-500">Faixa recomendada: 10 a 20.</p>
                                        @error('panelSidebarFontSize')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>

                            @php($produtoFormImagePreviewSize = old('produtoFormImagePreviewSize', $config->produtoFormImagePreviewSize ?? 48))
                            <div id="produto-form-preview" class="rounded-2xl border border-fuchsia-100 bg-white/85 p-5 shadow-sm">
                                <h4 class="mb-3 text-sm font-semibold text-slate-800">Preview de imagem no cadastro de produto</h4>
                                @if (!($produtoFormImagePreviewFeatureReady ?? false))
                                    <div class="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                        Recurso indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                                    </div>
                                @endif
                                <div class="max-w-xs">
                                    <label for="produtoFormImagePreviewSize" class="mb-1 block text-sm font-semibold">Tamanho do preview (px)</label>
                                    <input
                                        id="produtoFormImagePreviewSize"
                                        type="number"
                                        name="produtoFormImagePreviewSize"
                                        min="32"
                                        max="300"
                                        step="1"
                                        value="{{ $produtoFormImagePreviewSize }}"
                                        class="w-full rounded border px-3 py-2 text-sm"
                                        {{ ($produtoFormImagePreviewFeatureReady ?? false) ? '' : 'disabled' }}
                                    />
                                    <p class="mt-1 text-xs text-slate-500">Largura e altura da miniatura. Padrao: 48px. Faixa: 32 a 300.</p>
                                    @error('produtoFormImagePreviewSize')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            @php($panelBrandIconPreviewUrl = old('panelBrandIconUrl', $config->panelBrandIconUrl ?? ''))

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="rounded-2xl border border-white/70 bg-white/85 p-5 shadow-sm">
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

                                <div class="rounded-2xl border border-white/70 bg-white/85 p-5 shadow-sm">
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

                    <div id="integracao-asaas" class="scroll-mt-24 rounded-3xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50 via-white to-teal-100 p-6 shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Bloco 04</p>
                                <h3 class="text-lg font-semibold text-slate-900">Integração Asaas</h3>
                            </div>
                            <span class="inline-flex w-fit rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">Financeiro</span>
                        </div>
                        <p class="mb-4 text-sm text-slate-600">
                            @if (auth()->user()?->isDefaultAdmin())
                                Estas credenciais valem para clientes administrados diretamente pelo admin.
                            @else
                                Estas credenciais valem para a sua revenda e para os clientes da sua carteira que usarem Asaas.
                            @endif
                        </p>

                        <form method="POST" action="{{ route('admin.configadmin.update') }}" class="space-y-4 rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur-sm">
                            @csrf

                            @if (!($asaasConfigFeatureReady ?? false))
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    Recurso de configuracao do Asaas ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="asaasBaseUrl" class="mb-1 block text-sm font-semibold">Base URL do Asaas</label>
                                    <input id="asaasBaseUrl" name="asaasBaseUrl" type="url" value="{{ old('asaasBaseUrl', $asaasConfig->asaasBaseUrl ?? config('services.asaas.base_url')) }}" class="w-full rounded border px-3 py-2 text-sm">
                                    <p class="mt-1 text-xs text-slate-500">Exemplo: https://api-sandbox.asaas.com/v3</p>
                                    @error('asaasBaseUrl')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="asaasWebhookToken" class="mb-1 block text-sm font-semibold">Token do Webhook</label>
                                    <input id="asaasWebhookToken" name="asaasWebhookToken" type="text" value="{{ old('asaasWebhookToken', $asaasConfig->asaasWebhookToken ?? '') }}" class="w-full rounded border px-3 py-2 text-sm">
                                    <p class="mt-1 text-xs text-slate-500">Usado para validar os eventos enviados pelo Asaas para esta conta.</p>
                                    @error('asaasWebhookToken')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="asaasApiKey" class="mb-1 block text-sm font-semibold">API Key do Asaas</label>
                                <textarea id="asaasApiKey" name="asaasApiKey" rows="3" class="w-full rounded border px-3 py-2 text-sm">{{ old('asaasApiKey', $asaasConfig->asaasApiKey ?? '') }}</textarea>
                                <p class="mt-1 text-xs text-slate-500">Se deixar em branco, o sistema continua usando o fallback atual do ambiente.</p>
                                @error('asaasApiKey')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="pt-2">
                                <x-primary-button>Salvar integração Asaas</x-primary-button>
                            </div>
                        </form>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
