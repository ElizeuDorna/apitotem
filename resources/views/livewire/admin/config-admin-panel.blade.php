<div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-cyan-50 shadow-[0_26px_80px_rgba(15,23,42,0.10)]">
    <div class="p-6 text-gray-900 md:p-8">
        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        @if (session('warning'))
            <div class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">{{ session('warning') }}</div>
        @endif

        <div class="mb-6 rounded-3xl border border-slate-200/80 bg-gradient-to-r from-slate-900 via-slate-800 to-cyan-900 p-5 text-white shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-100/80">Navegacao da Tela</p>
                    <h3 class="mt-1 text-lg font-semibold text-white">Acesse cada grupo de configuracoes</h3>
                    <p class="mt-1 text-sm text-slate-200">Todos os blocos iniciam fechados. Clique em um item do menu para abrir apenas a area desejada.</p>
                </div>

                <nav class="flex flex-wrap gap-3 text-sm">
                    <button type="button" wire:click="toggleSection('tema')" class="inline-flex items-center rounded-full border px-4 py-2.5 font-semibold shadow-sm transition {{ $openSection === 'tema' ? 'border-cyan-200 bg-cyan-100 text-cyan-950 shadow-cyan-950/10' : 'border-cyan-400/40 bg-cyan-400/20 text-white hover:bg-cyan-400/30' }}">
                        Tema do painel
                    </button>
                    @if (auth()->user()?->isDefaultAdmin())
                        <button type="button" wire:click="toggleSection('apk')" class="inline-flex items-center rounded-full border px-4 py-2.5 font-semibold shadow-sm transition {{ $openSection === 'apk' ? 'border-sky-200 bg-sky-100 text-sky-950 shadow-sky-950/10' : 'border-sky-400/40 bg-sky-400/20 text-white hover:bg-sky-400/30' }}">
                            Upload APK
                        </button>
                    @endif
                    <button type="button" wire:click="toggleSection('identidade')" class="inline-flex items-center rounded-full border px-4 py-2.5 font-semibold shadow-sm transition {{ $openSection === 'identidade' ? 'border-violet-200 bg-violet-100 text-violet-950 shadow-violet-950/10' : 'border-violet-400/40 bg-violet-400/20 text-white hover:bg-violet-400/30' }}">
                        Identidade visual
                    </button>
                    <button type="button" wire:click="toggleSection('asaas')" class="inline-flex items-center rounded-full border px-4 py-2.5 font-semibold shadow-sm transition {{ $openSection === 'asaas' ? 'border-emerald-200 bg-emerald-100 text-emerald-950 shadow-emerald-950/10' : 'border-emerald-400/40 bg-emerald-400/20 text-white hover:bg-emerald-400/30' }}">
                        Integracao Asaas
                    </button>
                </nav>
            </div>
        </div>

        <div class="space-y-6">
            @if ($openSection === 'tema')
                <div id="tema-painel" class="scroll-mt-24 rounded-3xl border border-cyan-200/80 bg-gradient-to-br from-cyan-100 via-sky-50 to-white p-6 shadow-sm">
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
                        class="inline-flex w-full items-center justify-between rounded-2xl border border-cyan-200 bg-gradient-to-r from-white to-cyan-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:from-white hover:to-cyan-100"
                        @click="togglePanelTheme()"
                    >
                        <span x-text="panelTheme === 'dark' ? 'Tema escuro ativo' : 'Tema claro ativo'"></span>
                        <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs uppercase tracking-[0.12em] text-cyan-800" x-text="panelTheme === 'dark' ? 'Dark' : 'Light'"></span>
                    </button>
                </div>
            @endif

            @if (auth()->user()?->isDefaultAdmin() && $openSection === 'apk')
                <div id="upload-apk" class="scroll-mt-24 rounded-3xl border border-sky-200/80 bg-gradient-to-br from-sky-100 via-blue-50 to-white p-6 shadow-sm">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Bloco 02</p>
                            <h3 class="text-lg font-semibold text-slate-900">Upload APK Android</h3>
                        </div>
                        <span class="inline-flex w-fit rounded-full border border-sky-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700">Distribuicao</span>
                    </div>

                    <div class="mb-4 rounded-2xl border border-sky-100 bg-gradient-to-r from-white/90 to-sky-50 p-4 backdrop-blur-sm">
                        <p class="text-sm text-slate-600">
                            Envie o APK da WebView Android. O sistema publica sempre com o nome fixo <strong>install.apk</strong>.
                        </p>
                        <p class="mt-1 text-sm text-slate-600">
                            Download público: <a href="{{ $apkDownloadUrl }}" target="_blank" class="font-semibold text-indigo-700 hover:text-indigo-900">{{ $apkDownloadUrl }}</a>
                        </p>
                    </div>

                    <div class="mb-4 rounded-2xl border border-slate-200/80 bg-gradient-to-r from-white to-blue-50 p-4 shadow-sm">
                        <h4 class="mb-2 text-sm font-semibold text-slate-800">Arquivo atual</h4>
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

                    <form method="POST" action="{{ route('admin.apk-upload.store') }}" enctype="multipart/form-data" class="space-y-3 rounded-2xl border border-white/70 bg-gradient-to-br from-white to-sky-50 p-4 backdrop-blur-sm">
                        @csrf
                        <div>
                            <label for="apk_file" class="mb-1 block text-sm font-medium text-gray-700">Enviar novo APK</label>
                            <input id="apk_file" name="apk_file" type="file" accept=".apk,application/vnd.android.package-archive" class="w-full rounded border bg-white px-3 py-2 text-sm">
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

            @if ($openSection === 'identidade')
                @php($panelSidebarFontFamily = old('panelSidebarFontFamily', $config->panelSidebarFontFamily ?? ''))
                @php($panelSidebarFontSize = old('panelSidebarFontSize', $config->panelSidebarFontSize ?? '11'))
                @php($produtoFormImagePreviewSize = old('produtoFormImagePreviewSize', $config->produtoFormImagePreviewSize ?? 48))
                @php($panelBrandIconPreviewUrl = old('panelBrandIconUrl', $config->panelBrandIconUrl ?? ''))

                <div id="identidade-painel" class="scroll-mt-24 rounded-3xl border border-violet-200/80 bg-gradient-to-br from-violet-100 via-fuchsia-50 to-white p-6 shadow-sm">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-600">Bloco 03</p>
                            <h3 class="text-lg font-semibold text-slate-900">Identidade do Painel</h3>
                        </div>
                        <span class="inline-flex w-fit rounded-full border border-violet-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-violet-700">Branding</span>
                    </div>

                    <form method="POST" action="{{ route('admin.configadmin.update') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        @if (! $panelBrandIconFeatureReady)
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                Recurso de icone ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                            </div>
                        @endif

                        @if (! $panelSidebarFontFeatureReady)
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                Recurso de fonte da lateral ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                            </div>
                        @endif

                        <div id="fonte-lateral" class="rounded-2xl border border-violet-100 bg-gradient-to-br from-white to-violet-50 p-5 shadow-sm">
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

                        <div id="produto-form-preview" class="rounded-2xl border border-fuchsia-100 bg-gradient-to-br from-white to-fuchsia-50 p-5 shadow-sm">
                            <h4 class="mb-3 text-sm font-semibold text-slate-800">Preview de imagem no cadastro de produto</h4>
                            @if (! $produtoFormImagePreviewFeatureReady)
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
                                    {{ $produtoFormImagePreviewFeatureReady ? '' : 'disabled' }}
                                />
                                <p class="mt-1 text-xs text-slate-500">Largura e altura da miniatura. Padrao: 48px. Faixa: 32 a 300.</p>
                                @error('produtoFormImagePreviewSize')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-white/70 bg-gradient-to-br from-white to-violet-50 p-5 shadow-sm">
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

                            <div class="rounded-2xl border border-white/70 bg-gradient-to-br from-white to-fuchsia-50 p-5 shadow-sm">
                                <p class="mb-1 text-sm font-semibold">Pre-visualizacao atual</p>
                                <div class="flex h-24 w-24 items-center justify-center rounded-xl border bg-white shadow-sm">
                                    @if (is_string($panelBrandIconPreviewUrl) && trim($panelBrandIconPreviewUrl) !== '')
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
            @endif

            @if ($openSection === 'asaas')
                <div id="integracao-asaas" class="scroll-mt-24 rounded-3xl border border-emerald-200/80 bg-gradient-to-br from-emerald-100 via-teal-50 to-white p-6 shadow-sm">
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

                    <div class="mb-4 rounded-2xl border border-emerald-200 bg-white/85 p-4 text-sm text-emerald-950 shadow-sm">
                        <p class="font-semibold text-emerald-900">Importante: o webhook precisa ser cadastrado no portal do Asaas.</p>
                        <p class="mt-2">O sistema ja esta pronto para receber os eventos automaticamente, mas voce ainda precisa criar o webhook na conta do Asaas apontando para a URL publica abaixo.</p>
                        <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-xs text-emerald-900">
                            <p><strong>URL do webhook:</strong> {{ route('asaas.webhook.receive') }}</p>
                            <p class="mt-1"><strong>Token de autenticacao:</strong> use o mesmo token salvo neste formulario.</p>
                            <p class="mt-1"><strong>Eventos recomendados:</strong> PAYMENT_RECEIVED, PAYMENT_CONFIRMED, PAYMENT_OVERDUE, PAYMENT_UPDATED, PAYMENT_DELETED e PAYMENT_RESTORED.</p>
                        </div>
                        <p class="mt-3 text-xs text-emerald-800">Sem esse cadastro no portal do Asaas, o sistema ainda pode sincronizar por rotina agendada, mas o retorno do pagamento nao sera imediato.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.configadmin.update') }}" class="space-y-4 rounded-2xl border border-white/70 bg-gradient-to-br from-white to-emerald-50 p-5 shadow-sm backdrop-blur-sm">
                        @csrf

                        @if (! $asaasConfigFeatureReady)
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                Recurso de configuracao do Asaas ainda indisponivel neste ambiente. Execute as migrations pendentes para habilitar.
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="asaasBaseUrl" class="mb-1 block text-sm font-semibold">Base URL do Asaas</label>
                                <input id="asaasBaseUrl" name="asaasBaseUrl" type="url" value="{{ old('asaasBaseUrl', $asaasConfig->asaasBaseUrl ?? config('services.asaas.base_url')) }}" class="w-full rounded border px-3 py-2 text-sm">
                                <p class="mt-1 text-xs text-slate-500">Exemplo: https://api.asaas.com/v3</p>
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
            @endif
        </div>
    </div>
</div>