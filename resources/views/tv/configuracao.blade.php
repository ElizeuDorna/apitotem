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
                <div>
                    <label for="deviceToken" class="mb-1 block text-sm text-slate-300">Token da TV</label>
                    <input id="deviceToken" type="text" class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 focus:border-indigo-400 focus:outline-none" placeholder="Cole o token da TV">
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

                <div class="flex items-center gap-2 pt-2">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Salvar</button>
                    <button id="clearTvConfig" type="button" class="rounded-md border border-red-700 px-4 py-2 text-sm text-red-300 hover:bg-red-900/30">Limpar configurações</button>
                    <a href="{{ route('tv.totemweb') }}" class="rounded-md border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Voltar para TV</a>
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
</body>
</html>
