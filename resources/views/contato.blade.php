<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato | Tabela de Preço Digital</title>
    <meta name="description" content="Entre em contato para conhecer a solução de tabela de preço digital e solicitar uma demonstração.">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white">
    <header class="absolute inset-x-0 top-0 z-30">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-10">
            <a href="{{ route('home') }}" class="text-xl font-black tracking-[0.25em] uppercase text-white">Totem</a>
            <nav class="hidden items-center gap-6 text-sm text-white/80 md:flex">
                <a href="{{ route('home') }}" class="hover:text-white">Início</a>
                <a href="{{ url('/sobre') }}" class="hover:text-white">Sobre</a>
                <a href="{{ url('/contato') }}" class="text-cyan-300">Contato</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.18),_transparent_35%),linear-gradient(135deg,_#020617,_#0f172a_55%,_#164e63)]">
            <div class="mx-auto max-w-7xl px-6 pb-20 pt-32 lg:px-10 lg:pb-24 lg:pt-36">
                <div class="grid gap-10 lg:grid-cols-[1.05fr,0.95fr] lg:items-start">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.45em] text-cyan-300">Contato</p>
                        <h1 class="mt-6 text-4xl font-black tracking-tight md:text-6xl">Fale com a nossa equipe comercial.</h1>
                        <p class="mt-6 text-lg leading-8 text-white/75">Se você quer apresentar preços em telas digitais, reduzir retrabalho e atualizar conteúdo em tempo real, use os canais abaixo para falar com a equipe.</p>

                        <div class="mt-10 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300">Email</p>
                                <p class="mt-3 text-base font-semibold text-white">comercial@tabeladeprecodigital.com.br</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300">Telefone</p>
                                <p class="mt-3 text-base font-semibold text-white">(11) 99999-9999</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white p-6 text-slate-900 shadow-2xl lg:p-8">
                        <h2 class="text-2xl font-black tracking-tight">Envie sua mensagem</h2>
                        <p class="mt-2 text-sm text-slate-600">Preencha os dados abaixo. O formulário ainda é institucional e não envia para backend.</p>

                        <form action="#" method="POST" class="mt-8 space-y-4">
                            @csrf
                            <div>
                                <label for="nome" class="mb-1 block text-sm font-semibold text-slate-700">Nome</label>
                                <input id="nome" type="text" name="nome" placeholder="Seu nome" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div>
                                <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">E-mail</label>
                                <input id="email" type="email" name="email" placeholder="voce@empresa.com" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100">
                            </div>
                            <div>
                                <label for="mensagem" class="mb-1 block text-sm font-semibold text-slate-700">Mensagem</label>
                                <textarea id="mensagem" name="mensagem" rows="6" placeholder="Conte um pouco sobre o seu projeto" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100"></textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center rounded-full bg-slate-950 px-6 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white transition hover:bg-slate-800">
                                Enviar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
