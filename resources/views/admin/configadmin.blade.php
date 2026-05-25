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

                    <div id="tema-painel" class="rounded-lg border border-slate-200 bg-slate-50 p-4 mb-6 scroll-mt-24">
                        <h3 class="mb-2 text-base font-semibold text-slate-800">Tema do Painel</h3>
                        <p class="mb-3 text-sm text-slate-600">Alterne entre tema claro e escuro do painel administrativo.</p>
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                            @click="togglePanelTheme()"
                        >
                            <span x-text="panelTheme === 'dark' ? 'Tema escuro ativo' : 'Tema claro ativo'"></span>
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs uppercase tracking-[0.12em]" x-text="panelTheme === 'dark' ? 'Dark' : 'Light'"></span>
                        </button>
                    </div>

                    @if (auth()->user()?->isDefaultAdmin())
                        <div id="upload-apk" class="rounded-lg border border-slate-200 bg-slate-50 p-4 scroll-mt-24 mb-6">
                            <h3 class="mb-3 text-base font-semibold text-slate-800">Arquivos para Download</h3>

                            <div class="rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                                <p class="text-sm text-slate-600">
                                    O upload de APK e demais arquivos públicos agora é gerenciado em uma grade dedicada, com cadastro de múltiplos itens, edição, exclusão e links públicos individuais.
                                </p>
                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <a href="{{ route('admin.downloads.index') }}" class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Abrir grade de downloads
                                    </a>
                                    <a href="{{ route('downloads.public.index') }}" target="_blank" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">
                                        Ver lista pública de downloads
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div id="meta-instagram" class="rounded-lg border border-slate-200 bg-slate-50 p-4 scroll-mt-24 mb-6">
                            <h3 class="mb-3 text-base font-semibold text-slate-800">Meta / Instagram da Plataforma</h3>
                            <p class="mb-3 text-sm text-slate-600">Essa configuracao e global da plataforma. Cada empresa conecta o proprio Instagram, mas o sistema usa um unico app Meta da plataforma para iniciar a autorizacao.</p>

                            @if (!($metaIntegrationFeatureReady ?? false))
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    Recurso de configuracao global da Meta/Instagram ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                                </div>
                            @else
                                @php($metaAppId = old('metaAppId', $globalConfig->metaAppId ?? ''))
                                @php($metaRedirectUri = old('metaRedirectUri', $globalConfig->metaRedirectUri ?? ''))
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label for="metaAppId" class="mb-1 block text-sm font-semibold">Meta App ID</label>
                                            <input
                                                id="metaAppId"
                                                type="text"
                                                name="metaAppId"
                                                value="{{ $metaAppId }}"
                                                class="w-full rounded border px-3 py-2 text-sm"
                                                placeholder="Ex.: 123456789012345"
                                            />
                                            <p class="mt-1 text-xs text-slate-500">Identificador publico do app da plataforma criado no Meta for Developers.</p>
                                            @error('metaAppId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <label for="metaRedirectUri" class="mb-1 block text-sm font-semibold">Meta Redirect URI</label>
                                            <input
                                                id="metaRedirectUri"
                                                type="url"
                                                name="metaRedirectUri"
                                                value="{{ $metaRedirectUri }}"
                                                class="w-full rounded border px-3 py-2 text-sm"
                                                placeholder="https://seudominio.com/admin/rede-social/instagram/callback"
                                            />
                                            <p class="mt-1 text-xs text-slate-500">Precisa ser exatamente igual ao callback cadastrado na Meta.</p>
                                            @error('metaRedirectUri')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                                            <div class="font-semibold text-slate-900">App Secret</div>
                                            <div class="mt-1">Fica somente no servidor/.env por seguranca.</div>
                                            <div class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ ($metaAppSecretConfigured ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ ($metaAppSecretConfigured ?? false) ? 'Configurado no servidor' : 'Nao configurado no servidor' }}
                                            </div>
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                                            <div class="font-semibold text-slate-900">Graph API Version</div>
                                            <div class="mt-1">Definida no servidor/.env para controle tecnico da integracao.</div>
                                            <div class="mt-2 inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                {{ $metaGraphVersion ?? 'v22.0' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
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

                            @if (!($panelSidebarFontFeatureReady ?? false))
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    Recurso de fonte da lateral ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                                </div>
                            @endif

                            @php($panelSidebarFontFamily = old('panelSidebarFontFamily', $config->panelSidebarFontFamily ?? ''))
                            @php($panelSidebarFontSize = old('panelSidebarFontSize', $config->panelSidebarFontSize ?? '11'))

                            <div id="fonte-lateral" class="rounded-xl border border-slate-200 bg-white p-4">
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
                            <div id="produto-form-preview" class="rounded-xl border border-slate-200 bg-white p-4">
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
