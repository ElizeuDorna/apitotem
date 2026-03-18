<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TV Configuração</title>
    @vite(['resources/css/app.css', 'resources/js/tv-configuracao.js'])
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="mx-auto max-w-3xl p-4 md:p-8">
        <header class="mb-6 rounded-xl border border-slate-800 bg-slate-900 p-4">
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight">Configurações da TV</h1>
            <p class="text-sm text-slate-400">Defina token e parâmetros de consumo da API para a tela principal.</p>
        </header>

        <main class="rounded-xl border border-slate-800 bg-slate-900 p-4 md:p-6">
            <form id="tvConfigForm" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="deviceToken" class="mb-1 block text-sm text-slate-300">Token da TV</label>
                        <input id="deviceToken" type="text" class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none" placeholder="Cole o token da TV">
                    </div>
                    <div>
                        <label for="clientCode" class="mb-1 block text-sm text-slate-300">CODIGO CLIENTE</label>
                        <input id="clientCode" type="text" readonly class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm font-semibold tracking-wide text-emerald-300 focus:border-emerald-400 focus:outline-none" placeholder="Clique em gerar codigo">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="apiEndpoint" class="mb-1 block text-sm text-slate-300">Endpoint de Produtos</label>
                        <input id="apiEndpoint" type="text" class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none" value="/api/tv/produtos">
                    </div>

                    <div>
                        <label for="refreshSeconds" class="mb-1 block text-sm text-slate-300">Atualização (segundos)</label>
                        <input id="refreshSeconds" type="number" min="5" max="3600" class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none" value="30">
                    </div>
                </div>

                <div>
                    <label for="deviceUuid" class="mb-1 block text-sm text-slate-300">IDENTIFICACAO DO DISPOSITIVO</label>
                    <input id="deviceUuid" type="text" readonly class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-200 focus:border-slate-500 focus:outline-none" placeholder="Identificacao unica desta TV">
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Salvar</button>
                    <button id="clearTvConfig" type="button" class="rounded-md border border-red-700 px-4 py-2 text-sm text-red-300 hover:bg-red-900/30">Limpar configurações</button>
                    <a href="{{ route('tv.totemweb') }}" class="rounded-md border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Voltar para TV</a>
                    <button id="generateClientCode" type="button" class="ml-auto rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Gerar Codigo Cliente</button>
                </div>

                <p id="configStatus" class="text-xs text-slate-400">Ajuste as configurações e clique em Salvar.</p>

                <div class="rounded-md border border-slate-800 bg-slate-950/50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Historico de tokens</p>
                    <div id="tokenHistoryList" class="flex flex-wrap gap-2"></div>
                    <p class="mt-2 text-xs text-slate-500">Quando a internet cair, os tokens salvos continuam disponiveis para restauracao rapida.</p>
                </div>
            </form>
        </main>
    </div>

    <script>
        (function () {
            const form = document.getElementById('tvConfigForm');
            const tokenInput = document.getElementById('deviceToken');
            const endpointInput = document.getElementById('apiEndpoint');
            const refreshInput = document.getElementById('refreshSeconds');
            const statusText = document.getElementById('configStatus');

            if (!form || !tokenInput || !endpointInput || !refreshInput) {
                return;
            }

            const TOKEN_KEYS = [
                'tv_device_token',
                'tv_last_device_token',
                'tv_device_token_backup',
            ];

            const TV_CONFIG_ENDPOINT = '/api/tv/totemweb/config';
            const TV_PAGE_URL = '/tv/totemweb';

            const setStatus = (message, isError) => {
                if (!statusText) {
                    return;
                }

                statusText.textContent = message;
                statusText.className = `text-xs ${isError ? 'text-red-400' : 'text-slate-400'}`;
            };

            const persistToken = (token) => {
                TOKEN_KEYS.forEach((key) => localStorage.setItem(key, token));
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                event.stopImmediatePropagation();

                const token = String(tokenInput.value || '').trim();
                const endpoint = String(endpointInput.value || '').trim() || '/api/tv/produtos';
                const refresh = Number(refreshInput.value || 30);

                if (!token) {
                    setStatus('Informe o token da TV.', true);
                    return;
                }

                if (Number.isNaN(refresh) || refresh < 5 || refresh > 3600) {
                    setStatus('Atualizacao deve ficar entre 5 e 3600 segundos.', true);
                    return;
                }

                persistToken(token);
                localStorage.setItem('tv_api_endpoint', endpoint);
                localStorage.setItem('tv_refresh_seconds', String(refresh));

                setStatus('Validando token...');

                try {
                    const response = await fetch(TV_CONFIG_ENDPOINT, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (response.status === 401) {
                        const reason = String(payload && payload.reason ? payload.reason : '').toLowerCase();
                        if (reason === 'device_inactive') {
                            setStatus('Token encontrado, mas a TV esta desativada no admin.', true);
                            return;
                        }

                        if (reason === 'device_not_found') {
                            setStatus('Token nao encontrado. Gere novo codigo e ative novamente.', true);
                            return;
                        }

                        if (reason === 'token_missing') {
                            setStatus('Token nao informado.', true);
                            return;
                        }

                        setStatus('Token invalido para esta TV.', true);
                        return;
                    }

                    setStatus('Configuracoes salvas. Redirecionando para a TV...');
                    window.location.assign(TV_PAGE_URL);
                } catch (_error) {
                    setStatus('Falha momentanea na validacao. Redirecionando para a TV...');
                    window.location.assign(TV_PAGE_URL);
                }
            }, true);
        })();
    </script>
</body>
</html>
