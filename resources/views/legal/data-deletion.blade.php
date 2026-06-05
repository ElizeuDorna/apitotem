<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.public-favicon')
    <title>Exclusao de Dados do Usuario</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100">
    <main class="mx-auto max-w-4xl px-6 py-12 lg:px-8">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-cyan-950/20">
            <p class="text-sm font-semibold uppercase tracking-[0.35em] text-cyan-300">API Totem</p>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">Exclusao de Dados do Usuario</h1>
            <p class="mt-4 text-sm text-slate-300">Ultima atualizacao: {{ now()->format('d/m/Y') }}</p>

            <div class="mt-8 space-y-8 text-sm leading-7 text-slate-200">
                <section>
                    <h2 class="text-lg font-bold text-white">Como solicitar</h2>
                    <p class="mt-2">Para solicitar exclusao de dados vinculados ao uso da plataforma, o titular deve entrar em contato pelos canais oficiais da empresa responsavel pela operacao do sistema, informando dados suficientes para identificacao da conta e descricao da solicitacao.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">Validacao da solicitacao</h2>
                    <p class="mt-2">Antes da exclusao, a solicitacao pode passar por validacao de identidade e verificacao de legitimidade, a fim de evitar remocoes indevidas ou nao autorizadas.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">Prazo e escopo</h2>
                    <p class="mt-2">O pedido sera analisado dentro de prazo razoavel e cumprido quando tecnicamente possivel e juridicamente permitido. Dados sujeitos a obrigacoes legais, fiscais, regulatórias, seguranca ou auditoria podem ser mantidos pelo periodo exigido.</p>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-white">Integracoes externas</h2>
                    <p class="mt-2">Quando houver integracoes com plataformas de terceiros, a exclusao dentro da API Totem nao substitui os procedimentos exigidos diretamente por cada provedor externo para remocao completa de dados ou revogacao de acesso.</p>
                </section>
            </div>
        </div>
    </main>
</body>
</html>