<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $taxaTvsAtivas = $tvsTotal > 0 ? (($tvsAtivas / $tvsTotal) * 100) : 0;
        $taxaTvsOnline = $tvsTotal > 0 ? (($tvsOnline / $tvsTotal) * 100) : 0;
        $tvsOffline = max($tvsTotal - $tvsAtivas, 0);
        $mediaProdutosPorCliente = $clientesTotal > 0 ? ($produtosTotal / $clientesTotal) : 0;
    @endphp

    <div class="py-10 bg-slate-50/60">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-2xl border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 shadow-sm">
                <div class="border-b border-sky-100/70 px-6 py-5 sm:px-8">
                    <p class="text-xs uppercase tracking-[0.18em] text-sky-700">Painel Administrativo</p>
                    <h3 class="mt-2 text-2xl sm:text-3xl font-semibold text-slate-900">{{ $escopoTitulo }}</h3>
                    <p class="mt-1 text-sm text-slate-600">Indicadores consolidados de clientes, TVs e produtos.</p>

                    @if ($revendaPrecisaSelecionar && $temEmpresaAtiva)
                        <div class="mt-4 inline-flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm text-emerald-800">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Empresa ativa: <strong>{{ $empresaAtiva->nome }}</strong>
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-6 sm:p-8">
                    <article class="rounded-xl border border-cyan-100 bg-cyan-50/70 p-4">
                        <p class="text-xs uppercase tracking-wide text-cyan-700">TVs online agora</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($tvsOnline, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ number_format($taxaTvsOnline, 1, ',', '.') }}% da base</p>
                    </article>
                    <article class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4">
                        <p class="text-xs uppercase tracking-wide text-emerald-700">TVs ativas</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($tvsAtivas, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-600">de {{ number_format($tvsTotal, 0, ',', '.') }} TVs</p>
                    </article>
                    <article class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <p class="text-xs uppercase tracking-wide text-indigo-700">Clientes ativos</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($clientesAtivos, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-600">de {{ number_format($clientesTotal, 0, ',', '.') }} clientes</p>
                    </article>
                </div>
            </section>

            @if ($isAdminGeral)
                <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <article class="rounded-xl border border-sky-100 bg-sky-50/70 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Revendas ativas</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($revendasAtivas, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-600">de {{ number_format($revendasTotal, 0, ',', '.') }} revendas</p>
                    </article>
                    <article class="rounded-xl border border-violet-100 bg-violet-50/70 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Empresas vinculadas</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($clientesVinculadosRevenda, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-600">clientes finais ligados a revenda</p>
                    </article>
                    <article class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Clientes finais</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($clientesFinaisTotal, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-600">total de empresas cliente final</p>
                    </article>
                </section>
            @endif

            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <article class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Clientes ativos</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($clientesAtivos, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-600">de {{ number_format($clientesTotal, 0, ',', '.') }} clientes</p>
                </article>
                <article class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">TVs ativas</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($tvsAtivas, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-600">de {{ number_format($tvsTotal, 0, ',', '.') }} TVs</p>
                </article>
                <article class="rounded-xl border border-cyan-100 bg-cyan-50/60 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">TVs online</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($tvsOnline, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-600">atividade nos ultimos 5 minutos</p>
                </article>
                <article class="rounded-xl border border-amber-100 bg-amber-50/60 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Produtos</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($produtosTotal, 0, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-600">total no escopo atual</p>
                </article>
            </section>

            <section>
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h4 class="text-lg font-semibold text-slate-900">Resumo operacional</h4>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <p class="font-medium text-slate-700">Cobertura de TVs ativas</p>
                                <p class="font-semibold text-slate-900">{{ number_format($taxaTvsAtivas, 1, ',', '.') }}%</p>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-emerald-100">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min(100, max(0, $taxaTvsAtivas)) }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <p class="font-medium text-slate-700">Disponibilidade online</p>
                                <p class="font-semibold text-slate-900">{{ number_format($taxaTvsOnline, 1, ',', '.') }}%</p>
                            </div>
                            <div class="mt-2 h-2 w-full rounded-full bg-cyan-100">
                                <div class="h-2 rounded-full bg-cyan-500" style="width: {{ min(100, max(0, $taxaTvsOnline)) }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div class="rounded-lg border border-rose-100 bg-rose-50/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-rose-700">TVs offline</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($tvsOffline, 0, ',', '.') }}</p>
                            </div>
                            <div class="rounded-lg border border-indigo-100 bg-indigo-50/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-indigo-700">Media de produtos por cliente</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($mediaProdutosPorCliente, 1, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
