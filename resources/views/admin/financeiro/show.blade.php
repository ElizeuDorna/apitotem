<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Financeiro - {{ $empresa->nome }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <a href="{{ route('admin.financeiro.index') }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Voltar para grade
            </a>
        </div>

        <div lang="pt-BR" translate="no" class="max-w-5xl mx-auto rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-100 p-3 text-green-800">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-100 p-3 text-red-800">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Empresa</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $empresa->nome }}</p>
                    <p class="text-xs text-slate-600">{{ $empresa->cnpj_cpf }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quantidade de Dispositivos</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $quantidadeDispositivos }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Totais Atuais</p>
                    <p class="mt-1 text-sm text-slate-700">
                        @if ($isClienteFinal)
                            <span class="whitespace-nowrap [hyphens:none] [overflow-wrap:normal]">Conta a Pagar</span>
                        @elseif ($isRevenda && !($isEmpresaRevenda ?? false))
                            <span class="whitespace-nowrap [hyphens:none] [overflow-wrap:normal]">Conta a Receber do Cliente</span>
                        @elseif ($isAdmin)
                            <span class="whitespace-nowrap [hyphens:none] [overflow-wrap:normal]">Conta a Receber</span>
                        @else
                            Total
                        @endif
                        : <span class="font-semibold">R$ {{ number_format($totalReceber, 2, ',', '.') }}</span>
                    </p>
                </div>
            </div>

            @if ($isClienteFinal)
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="text-lg font-semibold text-slate-900">Resumo Financeiro</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-slate-500">Data de Vencimento</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $config->data_vencimento ? $config->data_vencimento->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Data de Aviso</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $config->data_aviso ? $config->data_aviso->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Data de Bloqueio</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $config->data_bloqueio ? $config->data_bloqueio->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">
                                @if($isClienteFinal)
                                    Valor Unitário a Pagar
                                @elseif($isRevenda)
                                    Valor Unitário da Cobrança
                                @else
                                    Valor Unitário a Receber
                                @endif
                            </p>
                            <p class="text-sm font-semibold text-slate-900">R$ {{ number_format((float) $config->valor_receber_unitario, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('admin.financeiro.update', $empresa->id) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data de Vencimento</label>
                            <input type="date" name="data_vencimento" value="{{ old('data_vencimento', optional($config->data_vencimento)->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data de Aviso</label>
                            <input type="date" name="data_aviso" value="{{ old('data_aviso', optional($config->data_aviso)->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data de Bloqueio</label>
                            <input type="date" name="data_bloqueio" value="{{ old('data_bloqueio', optional($config->data_bloqueio)->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">
                                @if($isRevenda)
                                    Valor Unitário para Cobrar do Cliente
                                @elseif($isAdmin && ($isEmpresaRevenda ?? false))
                                    Valor Unitário para Cobrar da Revenda
                                @else
                                    Valor Unitário a Receber
                                @endif
                            </label>
                            <input type="number" step="0.01" min="0" name="valor_receber_unitario" value="{{ old('valor_receber_unitario', $config->valor_receber_unitario) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Salvar configuração financeira
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
