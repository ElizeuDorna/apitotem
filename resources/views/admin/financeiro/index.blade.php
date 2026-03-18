<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Financeiro
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <x-back-button />
        </div>

        <div lang="pt-BR" translate="no" class="max-w-7xl mx-auto rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Contas a Pagar e Receber</h2>
                @if ($isClienteFinal)
                    <p class="mt-1 text-sm text-slate-600">Visualização de contas a pagar da sua empresa.</p>
                @elseif ($isRevenda)
                    <p class="mt-1 text-sm text-slate-600">Revenda possui contas a receber dos clientes da carteira e contas a pagar ao admin.</p>
                @else
                    <p class="mt-1 text-sm text-slate-600">Admin possui contas a receber de revendas e de clientes finais diretos.</p>
                @endif
            </div>

            @if ($isRevenda && $resumoRevenda)
                <div class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 whitespace-nowrap">Conta a Pagar ao Admin</p>
                        <p class="mt-1 text-lg font-bold text-amber-900">R$ {{ number_format($resumoRevenda['total_pagar_admin'], 2, ',', '.') }}</p>
                        <p class="text-xs text-amber-700 whitespace-nowrap">Valor por Dispositivo: R$ {{ number_format($resumoRevenda['valor_unitario_pagar_admin'], 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 whitespace-nowrap">Conta a Receber dos Clientes</p>
                        <p class="mt-1 text-lg font-bold text-emerald-900">R$ {{ number_format($resumoRevenda['total_receber_clientes'], 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Dispositivos da Carteira</p>
                        <p class="mt-1 text-lg font-bold text-slate-900">{{ $resumoRevenda['quantidade_dispositivos'] }}</p>
                    </div>
                </div>
            @endif

            @if ($isAdmin)
                <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50/70 p-4">
                    <form method="GET" action="{{ route('admin.financeiro.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">
                        <div>
                            <label for="nivel" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-blue-900">Nível</label>
                            <select id="nivel" name="nivel" class="rounded-md border border-blue-300 bg-white px-3 py-2 text-sm text-slate-800">
                                <option value="cliente_final" @selected(($nivelSelecionado ?? 'cliente_final') === 'cliente_final')>Nível 1 - Cliente Final</option>
                                <option value="revenda" @selected(($nivelSelecionado ?? '') === 'revenda')>Nível 2 - Revenda</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Aplicar</button>
                        </div>
                    </form>

                    @if (($nivelSelecionado ?? '') === 'revenda' && (int) ($revendaSelecionadaId ?? 0) > 0)
                        <div class="mt-3 flex items-center justify-between rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm text-blue-900">
                            <span>Revenda selecionada: <strong>{{ $revendaSelecionadaNome ?? ('ID #' . $revendaSelecionadaId) }}</strong></span>
                            <a href="{{ route('admin.financeiro.index', ['nivel' => 'revenda']) }}" class="rounded-md border border-blue-300 px-3 py-1 text-xs font-semibold text-blue-800 hover:bg-blue-50">Voltar para lista de revendas</a>
                        </div>
                    @endif
                </div>
            @endif

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

            <div class="overflow-x-auto rounded-xl border border-slate-300">
                <table class="w-full border-collapse">
                    <thead class="border-b border-blue-200 bg-blue-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">Empresa</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">CNPJ/CPF</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">Qtd Dispositivos</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-800">
                                @if($isClienteFinal)
                                    <span class="whitespace-nowrap [hyphens:none] [overflow-wrap:normal]">Conta a Pagar</span>
                                @else
                                    <span class="whitespace-nowrap [hyphens:none] [overflow-wrap:normal]">Conta a Receber</span>
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-300">
                        @forelse($linhas as $linha)
                            @php
                                $empresa = $linha['empresa'];
                            @endphp
                            <tr
                                class="cursor-pointer odd:bg-white even:bg-slate-100 hover:bg-sky-100 transition-colors duration-150"
                                onclick="window.location.href='{{ $linha['click_url'] }}'"
                            >
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $empresa->nome }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->cnpj_cpf }}</td>
                                <td class="px-4 py-3 text-center text-sm font-semibold text-slate-800">{{ $linha['quantidade_dispositivos'] }}</td>
                                <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">R$ {{ number_format($linha['valor_total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                    Nenhum registro financeiro encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
