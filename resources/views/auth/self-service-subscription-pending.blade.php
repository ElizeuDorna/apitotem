<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Assinatura pendente
        </h2>
    </x-slot>

    @php
        $qrCodeSrc = null;
        if ($charge->pix_qr_code) {
            $qrCodeSrc = str_starts_with((string) $charge->pix_qr_code, 'data:')
                ? $charge->pix_qr_code
                : 'data:image/png;base64,' . $charge->pix_qr_code;
        }
    @endphp

    <div class="py-10 bg-slate-50/60">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-3xl border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-emerald-50 p-8 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Auto cadastro concluído</p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900">Sua empresa já está criada</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    A conta da empresa <span class="font-semibold text-slate-900">{{ $empresa->nome }}</span> entrou em {{ strtoupper($subscription->normalizedStatus()) }}.
                    Para manter a assinatura ativa após o período promocional, use a primeira cobrança abaixo.
                </p>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <article class="rounded-2xl border border-white/80 bg-white/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Plano</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $subscription->plan_name ?: 'Plano ativo' }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/80 bg-white/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Fim do trial</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ optional($subscription->trial_ends_at)->format('d/m/Y') ?: '-' }}</p>
                    </article>
                    <article class="rounded-2xl border border-white/80 bg-white/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Cobrança</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $charge->statusLabel() }}</p>
                    </article>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-[320px_1fr]">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">QR Code PIX</h3>
                    <div class="mt-5 flex min-h-[260px] items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        @if ($qrCodeSrc)
                            <img src="{{ $qrCodeSrc }}" alt="QR Code PIX" class="h-56 w-56 rounded-xl border border-slate-200 bg-white p-2">
                        @else
                            <p class="text-center text-sm text-slate-500">O QR Code ainda não está disponível. Use a fatura ou tente novamente em instantes.</p>
                        @endif
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Primeira cobrança</h3>
                            <p class="mt-1 text-sm text-slate-600">A liberação e renovação da assinatura acontecem automaticamente após a confirmação do pagamento.</p>
                        </div>
                        @if ($charge->invoice_url)
                            <a href="{{ $charge->invoice_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-md border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                                Abrir fatura
                            </a>
                        @endif
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Valor</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">R$ {{ number_format((float) $charge->valor_total, 2, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Vencimento</p>
                            <p class="mt-1 text-xl font-semibold text-slate-900">{{ optional($charge->vencimento)->format('d/m/Y') ?: '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Descrição</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $charge->descricao }}</p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">PIX copia e cola</p>
                        <textarea readonly rows="4" class="mt-2 w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm text-slate-700">{{ $charge->pix_copy_paste }}</textarea>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('admin.financeiro.show', $empresa) }}" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Ver detalhes da cobrança
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Ir para o dashboard
                        </a>
                    </div>
                </article>
            </section>
        </div>
    </div>
</x-app-layout>