<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Conectar WhatsApp pela Meta
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <x-back-button />
        </div>

        <div
            class="mx-auto max-w-5xl space-y-6 px-4"
            x-data="whatsappEmbeddedSignupPage(@js($embeddedSignup))"
        >
            <section class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm shadow-emerald-100/70">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Onboarding automatico do WhatsApp</h1>
                        <p class="mt-2 text-sm text-slate-600">Esse fluxo abre o Embedded Signup da Meta para a empresa ativa, troca o code por business token no servidor e salva a integracao sem apagar o formulario manual que ja funciona.</p>
                    </div>
                    <div class="rounded-2xl px-4 py-2 text-sm font-semibold {{ $integration?->status === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $integration?->status === 'connected' ? 'Integracao ativa' : 'Ainda nao conectado' }}
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="space-y-4 rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Empresa ativa</h2>
                            <p class="mt-1 text-sm text-slate-600">A conexao sera salva para esta empresa.</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-700">
                            <div class="font-semibold text-slate-900">{{ $empresa->nome_fantasia ?: $empresa->nome }}</div>
                            <div class="mt-1 text-xs text-slate-500">ID interno da empresa: {{ $empresa->id }}</div>
                            @if ($integration)
                                <div class="mt-3 space-y-1 text-xs text-slate-600">
                                    <div>WABA atual: {{ $integration->meta_business_account_id ?: 'nao informado' }}</div>
                                    <div>Phone Number ID atual: {{ $integration->meta_phone_number_id ?: 'nao informado' }}</div>
                                    <div>Numero exibido: {{ $integration->display_phone_number ?: 'nao informado' }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-900">
                            <div class="font-semibold">O que este fluxo faz</div>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-sky-900">
                                <li>abre o login da Meta com a configuracao de WhatsApp Embedded Signup</li>
                                <li>captura code, WABA ID e Phone Number ID</li>
                                <li>assina webhooks na WABA</li>
                                <li>registra o numero para Cloud API</li>
                                <li>salva a integracao na mesma tabela usada pelo envio atual</li>
                            </ul>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                        @if (! $embeddedSignup['enabled'])
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                                <div class="font-semibold">O onboarding automatico ainda nao pode abrir.</div>
                                <div class="mt-2">Variaveis faltando no servidor: {{ implode(', ', $embeddedSignupMissingKeys) }}.</div>
                                <div class="mt-2">Depois de preencher essas variaveis no .env, limpe o cache de configuracao com <span class="font-semibold">./vendor/bin/sail artisan optimize:clear</span>.</div>
                            </div>
                        @else
                            <div>
                                <label for="embedded-signup-pin" class="mb-1 block text-sm font-semibold text-slate-800">PIN de 6 digitos do numero</label>
                                <input id="embedded-signup-pin" type="text" inputmode="numeric" maxlength="6" x-model="pin" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm" placeholder="123456">
                                <p class="mt-1 text-xs text-slate-500">A Meta exige esse PIN para registrar o numero no Cloud API. Guarde esse PIN para futuras manutencoes.</p>
                            </div>

                            <button
                                type="button"
                                @click="launchSignup"
                                :disabled="busy"
                                class="inline-flex w-full items-center justify-center rounded-2xl border border-emerald-600 bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span x-show="!busy">Conectar WhatsApp pela Meta</span>
                                <span x-show="busy">Processando onboarding...</span>
                            </button>
                        @endif

                        <div x-show="statusMessage" x-cloak class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" x-text="statusMessage"></div>
                        <div x-show="errorMessage" x-cloak class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" x-text="errorMessage"></div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-xs text-slate-600">
                            <div class="font-semibold text-slate-900">Pre-requisitos fora do sistema</div>
                            <div class="mt-2 space-y-1">
                                <div>Allowed domains e redirect URIs configurados no app da Meta</div>
                                <div>Configuracao Facebook Login for Business criada para WhatsApp Embedded Signup</div>
                                <div>Permissoes de WhatsApp aprovadas para producao quando sair do modo teste</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        function whatsappEmbeddedSignupPage(config) {
            return {
                busy: false,
                pin: '',
                statusMessage: '',
                errorMessage: '',
                code: null,
                sessionData: null,
                waitingForSessionDataTimeoutId: null,
                init() {
                    window.addEventListener('message', (event) => {
                        if (! String(event.origin || '').endsWith('facebook.com')) {
                            return;
                        }

                        try {
                            const payload = typeof event.data === 'string'
                                ? JSON.parse(event.data)
                                : event.data;

                            if (! payload || typeof payload !== 'object') {
                                return;
                            }

                            if (payload.type !== 'WA_EMBEDDED_SIGNUP') {
                                return;
                            }

                            this.handleEmbeddedEvent(payload);
                        } catch (_) {
                            if (typeof event.data === 'string' && event.data.includes('WA_EMBEDDED_SIGNUP')) {
                                this.errorMessage = 'A Meta enviou um retorno de onboarding em formato inesperado. Abra o console do navegador para inspecionar o message event bruto.';
                            }
                        }
                    });
                },
                async launchSignup() {
                    this.errorMessage = '';
                    this.statusMessage = '';

                    if (! config.enabled) {
                        this.errorMessage = 'O onboarding automatico ainda nao foi configurado no servidor.';
                        return;
                    }

                    if (! /^\d{6}$/.test(this.pin)) {
                        this.errorMessage = 'Informe um PIN de exatamente 6 digitos antes de abrir a Meta.';
                        return;
                    }

                    this.busy = true;
                    this.statusMessage = 'Abrindo login da Meta...';

                    try {
                        await this.ensureSdkLoaded();

                        window.FB.login((response) => this.handleLoginResponse(response), {
                            config_id: config.configurationId,
                            response_type: 'code',
                            override_default_response_type: true,
                            extras: {
                                setup: {},
                            },
                        });
                    } catch (error) {
                        this.busy = false;
                        this.errorMessage = error.message || 'Nao foi possivel abrir o Embedded Signup da Meta.';
                    }
                },
                handleEmbeddedEvent(payload) {
                    if (String(payload.event || '').startsWith('FINISH')) {
                        this.sessionData = this.normalizeSessionData(payload.data || {});
                        if (this.hasCompleteSessionData()) {
                            this.clearWaitingForSessionDataTimeout();
                        }
                        this.statusMessage = 'A Meta retornou os dados da empresa. Finalizando cadastro no servidor...';
                        this.tryFinalize();

                        if (! this.hasCompleteSessionData()) {
                            this.startWaitingForSessionDataTimeout();
                        }

                        return;
                    }

                    this.clearWaitingForSessionDataTimeout();
                    this.busy = false;

                    if (payload.data && payload.data.error_message) {
                        this.errorMessage = payload.data.error_message;
                        return;
                    }

                    if (payload.data && payload.data.current_step) {
                        this.errorMessage = `Fluxo cancelado na etapa ${payload.data.current_step}.`;
                        return;
                    }

                    this.errorMessage = 'O fluxo da Meta foi cancelado antes da conclusao.';
                },
                handleLoginResponse(response) {
                    if (response && response.authResponse && response.authResponse.code) {
                        this.code = response.authResponse.code;
                        this.statusMessage = 'Meta autorizou o app. Aguardando os IDs do numero e da WABA...';
                        this.startWaitingForSessionDataTimeout();
                        this.tryFinalize();
                        return;
                    }

                    this.clearWaitingForSessionDataTimeout();
                    this.busy = false;
                    this.errorMessage = 'A Meta nao retornou um codigo de autorizacao valido.';
                },
                async tryFinalize() {
                    if (! this.code || ! this.hasCompleteSessionData()) {
                        return;
                    }

                    this.statusMessage = 'Salvando integracao e registrando o numero no servidor...';

                    try {
                        const response = await fetch(config.onboardUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                code: this.code,
                                waba_id: this.sessionData.waba_id,
                                phone_number_id: this.sessionData.phone_number_id,
                                business_id: this.sessionData.business_id || null,
                                two_step_verification_pin: this.pin,
                            }),
                        });

                        const payload = await response.json();

                        if (! response.ok || ! payload.success) {
                            throw new Error(payload.message || 'Falha ao concluir o onboarding da Meta.');
                        }

                        this.clearWaitingForSessionDataTimeout();
                        this.statusMessage = payload.message || 'WhatsApp conectado com sucesso.';
                        window.location.href = config.redirectUrl;
                    } catch (error) {
                        this.clearWaitingForSessionDataTimeout();
                        this.busy = false;
                        this.errorMessage = error.message || 'Falha ao concluir o onboarding da Meta.';
                    }
                },
                startWaitingForSessionDataTimeout() {
                    this.clearWaitingForSessionDataTimeout();

                    this.waitingForSessionDataTimeoutId = window.setTimeout(() => {
                        if (this.hasCompleteSessionData()) {
                            return;
                        }

                        this.busy = false;
                        this.errorMessage = 'A Meta autorizou o app, mas nao devolveu os IDs do numero e da WABA. Confirme que o fluxo foi concluido na tela final da Meta e revise Allowed Domains, URIs de redirecionamento OAuth validas e SDK JavaScript.';
                    }, 20000);
                },
                hasCompleteSessionData() {
                    return Boolean(this.sessionData && this.sessionData.phone_number_id && this.sessionData.waba_id);
                },
                normalizeSessionData(data) {
                    const source = data && typeof data === 'object' ? data : {};
                    const normalizeString = (value) => typeof value === 'string' ? value.trim() : '';
                    const firstString = (...values) => {
                        for (const value of values) {
                            if (Array.isArray(value)) {
                                for (const nested of value) {
                                    const normalizedNested = normalizeString(nested);
                                    if (normalizedNested !== '') {
                                        return normalizedNested;
                                    }
                                }

                                continue;
                            }

                            const normalized = normalizeString(value);
                            if (normalized !== '') {
                                return normalized;
                            }
                        }

                        return '';
                    };

                    const normalized = {
                        ...source,
                        phone_number_id: firstString(
                            source.phone_number_id,
                            source.phoneNumberId,
                            source.phone?.id,
                            source.phone?.phone_number_id,
                            source.sessionInfo?.phone_number_id,
                            source.sessionInfo?.phoneNumberId,
                            source.asset_ids?.phone_number_id,
                            source.asset_ids?.phoneNumberId,
                        ),
                        waba_id: firstString(
                            source.waba_id,
                            source.wabaId,
                            source.waba?.id,
                            source.sessionInfo?.waba_id,
                            source.sessionInfo?.wabaId,
                            source.asset_ids?.waba_id,
                            source.asset_ids?.wabaId,
                            source.waba_ids,
                            source.sessionInfo?.waba_ids,
                        ),
                        business_id: firstString(
                            source.business_id,
                            source.businessId,
                            source.business?.id,
                            source.sessionInfo?.business_id,
                            source.sessionInfo?.businessId,
                        ),
                    };

                    return normalized;
                },
                clearWaitingForSessionDataTimeout() {
                    if (this.waitingForSessionDataTimeoutId) {
                        window.clearTimeout(this.waitingForSessionDataTimeoutId);
                        this.waitingForSessionDataTimeoutId = null;
                    }
                },
                ensureSdkLoaded() {
                    if (window.FB) {
                        return Promise.resolve();
                    }

                    if (window.__whatsappEmbeddedSignupSdkPromise) {
                        return window.__whatsappEmbeddedSignupSdkPromise;
                    }

                    window.__whatsappEmbeddedSignupSdkPromise = new Promise((resolve, reject) => {
                        window.fbAsyncInit = function () {
                            window.FB.init({
                                appId: config.appId,
                                autoLogAppEvents: true,
                                xfbml: false,
                                version: config.graphVersion,
                            });

                            resolve();
                        };

                        const existingScript = document.querySelector('script[data-meta-sdk="whatsapp-embedded-signup"]');
                        if (existingScript) {
                            return;
                        }

                        const script = document.createElement('script');
                        script.async = true;
                        script.defer = true;
                        script.crossOrigin = 'anonymous';
                        script.dataset.metaSdk = 'whatsapp-embedded-signup';
                        script.src = 'https://connect.facebook.net/en_US/sdk.js';
                        script.onerror = () => reject(new Error('Nao foi possivel carregar o SDK da Meta.'));

                        document.head.appendChild(script);
                    });

                    return window.__whatsappEmbeddedSignupSdkPromise;
                },
            };
        }
    </script>
</x-app-layout>