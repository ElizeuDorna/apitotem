<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.public-favicon')
    <title>Termos de Servico</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100">
    <main class="mx-auto max-w-4xl px-6 py-12 lg:px-8">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-cyan-950/20">
            <p class="text-sm font-semibold uppercase tracking-[0.35em] text-cyan-300">API Totem</p>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">Termos de Servico</h1>
            <p class="mt-4 text-sm text-slate-300">Ultima atualizacao: {{ now()->format('d/m/Y') }}</p>

            <div class="mt-8 space-y-8 text-sm leading-7 text-slate-200">
                <section>
                    <h2 class="text-lg font-bold text-white">1. Objeto</h2>
                    <p class="mt-2">A plataforma API Totem oferece recursos de gestao, integracao, exibicao de conteudo, operacao de dispositivos e modulos conectados, incluindo recursos de terceiros quando habilitados pelo usuario.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">2. Responsabilidades do usuario</h2>
                    <p class="mt-2">O usuario e responsavel pela veracidade dos dados informados, pelo uso adequado da plataforma, pela guarda de credenciais, pelo cumprimento de leis aplicaveis e pela autorizacao de integracoes de terceiros que ativar.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">3. Disponibilidade e alteracoes</h2>
                    <p class="mt-2">A plataforma pode passar por manutencoes, evolucoes, ajustes de seguranca e atualizacoes funcionais a qualquer tempo, com o objetivo de preservar estabilidade, continuidade e melhoria do servico.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">4. Integracoes de terceiros</h2>
                    <p class="mt-2">Recursos conectados a plataformas externas, como servicos Meta e WhatsApp, dependem das regras, disponibilidade, limites e aprovacoes definidos pelos respectivos provedores.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">5. Suspensao e encerramento</h2>
                    <p class="mt-2">O acesso pode ser suspenso ou encerrado em caso de uso indevido, risco de seguranca, violacao contratual, exigencia legal ou descontinuidade operacional do servico.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">6. Limitacao de responsabilidade</h2>
                    <p class="mt-2">A responsabilidade da plataforma observa os limites legais e contratuais aplicaveis, incluindo indisponibilidades ou restricoes decorrentes de provedores externos e configuracoes sob responsabilidade do usuario.</p>
                </section>
            </div>
        </div>
    </main>
</body>
</html>