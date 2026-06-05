<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.public-favicon')
    <title>Politica de Privacidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100">
    <main class="mx-auto max-w-4xl px-6 py-12 lg:px-8">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-cyan-950/20">
            <p class="text-sm font-semibold uppercase tracking-[0.35em] text-cyan-300">API Totem</p>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">Politica de Privacidade</h1>
            <p class="mt-4 text-sm text-slate-300">Ultima atualizacao: {{ now()->format('d/m/Y') }}</p>

            <div class="mt-8 space-y-8 text-sm leading-7 text-slate-200">
                <section>
                    <h2 class="text-lg font-bold text-white">1. Dados coletados</h2>
                    <p class="mt-2">A plataforma API Totem pode coletar dados necessarios para autenticacao, configuracao de contas, integracoes com Meta/WhatsApp, cadastros operacionais, dispositivos, produtos e informacoes tecnicas de acesso para funcionamento do servico.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">2. Uso das informacoes</h2>
                    <p class="mt-2">Os dados sao utilizados para operar a plataforma, permitir integracoes autorizadas pelo usuario, registrar configuracoes, enviar e receber comunicacoes de servicos contratados, manter seguranca, auditar acessos e cumprir obrigacoes legais e regulatórias.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">3. Compartilhamento</h2>
                    <p class="mt-2">Os dados podem ser compartilhados somente quando necessario para execucao de integracoes solicitadas pelo usuario, com provedores de infraestrutura, meios de comunicacao e parceiros tecnologicos, sempre dentro do escopo operacional do servico.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">4. Armazenamento e seguranca</h2>
                    <p class="mt-2">Adotamos medidas tecnicas e administrativas para proteger os dados contra acesso nao autorizado, alteracao indevida, divulgacao ou destruicao. O tempo de retencao observa necessidade operacional, contratual e legal.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">5. Direitos do titular</h2>
                    <p class="mt-2">O titular pode solicitar informacoes sobre tratamento de dados, correcao, atualizacao e exclusao quando aplicavel, observadas obrigacoes legais de retencao e restricoes tecnicas ligadas a seguranca e auditoria.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">6. Contato</h2>
                    <p class="mt-2">Solicitacoes relacionadas a privacidade e dados pessoais podem ser enviadas pelos canais oficiais da empresa responsavel pela operacao da plataforma.</p>
                </section>
            </div>
        </div>
    </main>
</body>
</html>