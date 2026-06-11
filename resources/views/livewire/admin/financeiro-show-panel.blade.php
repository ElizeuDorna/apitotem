<div lang="pt-BR" translate="no" class="max-w-5xl mx-auto rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50 to-cyan-50 p-8 shadow-[0_24px_70px_rgba(15,23,42,0.10)]">
    @if ($successMessage)
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => { show = false; $wire.set('successMessage', null); }, 3500)"
            x-show="show"
            x-transition.opacity.duration.400ms
            x-cloak
            class="pointer-events-none fixed inset-x-0 top-20 z-50 flex justify-center px-4"
            wire:key="financeiro-success-toast-{{ $successToastKey }}"
        >
            <div class="pointer-events-auto w-full max-w-xl rounded-2xl border border-emerald-200 bg-emerald-500 px-5 py-4 text-sm font-semibold text-white shadow-2xl shadow-emerald-900/20">
                {{ $successMessage }}
            </div>
        </div>
    @endif

    @php
        $canEditConfig = ! ($isAdmin && (int) $empresa->nivel_acesso === \App\Models\Empresa::NIVEL_CLIENTE_FINAL && $empresa->revenda_id !== null);
        $showPixSection = ! ($isEmpresaRevenda ?? false);
    @endphp

    @if (session('success') && ! $successMessage)
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
        <div class="rounded-2xl border border-cyan-200/70 bg-gradient-to-br from-cyan-500 to-sky-600 p-5 text-white shadow-lg shadow-cyan-900/10">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-50/80">Empresa</p>
            <p class="mt-2 text-base font-semibold text-white">{{ $empresa->nome }}</p>
            <p class="text-xs text-cyan-50/80">{{ $empresa->cnpj_cpf }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-400 to-teal-600 p-5 text-white shadow-lg shadow-emerald-900/10">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-50/80">Dispositivos Ativos</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ $quantidadeDispositivos }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200/70 bg-gradient-to-br from-amber-300 via-orange-300 to-rose-400 p-5 text-slate-950 shadow-lg shadow-orange-900/10">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-800/70">Totais Atuais</p>
            <p class="mt-2 text-sm text-slate-900">
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
            <p class="mt-2 text-xs text-slate-800/75">{{ $quantidadeDispositivos }} dispositivo(s) x {{ $mesesCobranca }} mes(es) x R$ {{ number_format((float) $valorReceberUnitario, 2, ',', '.') }}</p>
        </div>
    </div>

    @if ($isClienteFinal)
        <div class="rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Resumo Financeiro</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Data-base do Ciclo</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $config->data_vencimento ? $config->data_vencimento->format('d/m/Y') : '-' }}</p>
                    <p class="mt-1 text-xs text-slate-500">Usada como referência para sugerir a próxima cobrança recorrente.</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-600">Data de Aviso</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $config->data_aviso ? $config->data_aviso->format('d/m/Y') : '-' }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-500">Data de Bloqueio</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $config->data_bloqueio ? $config->data_bloqueio->format('d/m/Y') : '-' }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-500">Intervalo de Cobrança</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $billingIntervalLabel }}</p>
                    <p class="mt-1 text-xs text-slate-500">Equivale a {{ $mesesCobranca }} mes(es) por ciclo.</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Cobrança Automática</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $config->automaticBillingStatusLabel() }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/70 p-4 backdrop-blur-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">
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
    @elseif ($canEditConfig)
        <form wire:submit="saveFinanceiroConfig" class="space-y-5">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data-base do Ciclo</label>
                    <input type="date" wire:model="dataVencimento" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                    <p class="mt-1 text-xs text-slate-500">Referência usada para sugerir o próximo vencimento recorrente.</p>
                    @error('dataVencimento')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data de Aviso</label>
                    <input type="date" wire:model="dataAviso" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                    @error('dataAviso')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data de Bloqueio</label>
                    <input type="date" wire:model="dataBloqueio" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                    @error('dataBloqueio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Intervalo de Cobrança</label>
                    <select wire:model="intervaloCobrancaDias" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        @foreach ($billingIntervalOptions as $intervaloDias => $intervaloLabel)
                            <option value="{{ $intervaloDias }}">{{ $intervaloLabel }}</option>
                        @endforeach
                    </select>
                    @error('intervaloCobrancaDias')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-2xl border border-violet-200 bg-gradient-to-r from-violet-50 via-fuchsia-50 to-sky-50 p-5 text-sm text-slate-700 shadow-sm">
                <p class="font-semibold text-slate-900">Como o ciclo funciona</p>
                <p class="mt-2">A <span class="font-medium">data-base do ciclo</span> serve de referência para calcular a próxima cobrança. O <span class="font-medium">vencimento da cobrança</span> é a data real da fatura que será gerada.</p>
                <p class="mt-2">Exemplos: mensal soma 30 dias, trimestral soma 90 dias, semestral soma 180 dias e anual soma 365 dias a partir da última cobrança gerada ou da data-base configurada.</p>
                <p class="mt-2">O total do ciclo segue o plano: <span class="font-medium">dispositivos ativos x valor unitário x meses do intervalo</span>.</p>
                <p class="mt-2">Se a cobrança automática estiver ativada, o sistema verifica diariamente se chegou ao próximo ciclo e gera a cobrança PIX sem ação manual.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Meses do Ciclo</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-950">{{ $mesesCobranca }}</p>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Total por Dispositivo</p>
                    <p class="mt-1 text-2xl font-bold text-sky-950">R$ {{ number_format(((float) $valorReceberUnitario) * $mesesCobranca, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Total do Ciclo</p>
                    <p class="mt-1 text-2xl font-bold text-amber-950">R$ {{ number_format($totalReceber, 2, ',', '.') }}</p>
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
                    <input type="number" step="0.01" min="0" wire:model="valorReceberUnitario" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                    @error('valorReceberUnitario')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Cobrança Automática</label>
                    <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="cobrancaAutomaticaAtiva" value="1">
                        <span>Gerar cobrança automaticamente conforme o ciclo configurado</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Integração Asaas</label>
                    <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="asaasIntegrationAtiva" value="1">
                        <span>Ativar cobrança e status pelo Asaas para este cliente</span>
                    </label>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Bloqueio da TV</label>
                    <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="bloquearTvInadimplencia" value="1">
                        <span>Bloquear a TV automaticamente quando houver inadimplência</span>
                    </label>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">QR Code na TV</label>
                    <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="exibirQrCodeTvBloqueada" value="1">
                        <span>Mostrar QR Code da cobrança na tela bloqueada</span>
                    </label>
                </div>
            </div>

            <div class="rounded-2xl border border-sky-200 bg-gradient-to-r from-sky-50 via-cyan-50 to-blue-50 p-5 text-sm text-sky-900 shadow-sm">
                <p class="font-semibold text-sky-950">Como as credenciais do Asaas são escolhidas</p>
                <p class="mt-2">Cliente final vinculado diretamente ao admin usa as credenciais globais salvas em Config Admin.</p>
                <p class="mt-2">Cliente final vinculado a uma revenda usa as credenciais da própria revenda em Config Admin.</p>
                <p class="mt-2">Se a integração estiver desativada para este cliente, o sistema não gera cobrança nem usa status do Asaas para bloquear ou liberar a TV.</p>
            </div>

            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveFinanceiroConfig" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                    Salvar configuração financeira
                </button>
            </div>
        </form>
    @else
        <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 via-orange-50 to-rose-50 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-amber-950">Modo somente leitura</h3>
            <p class="mt-2 text-sm text-amber-900">
                Este cliente está vinculado a uma revenda. O admin pode acompanhar as cobranças, mas a configuração e a emissão do PIX ficam com a revenda responsável.
            </p>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs text-amber-700">Data-base do Ciclo</p>
                    <p class="text-sm font-semibold text-amber-950">{{ $config->data_vencimento ? $config->data_vencimento->format('d/m/Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-amber-700">Data de Aviso</p>
                    <p class="text-sm font-semibold text-amber-950">{{ $config->data_aviso ? $config->data_aviso->format('d/m/Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-amber-700">Data de Bloqueio</p>
                    <p class="text-sm font-semibold text-amber-950">{{ $config->data_bloqueio ? $config->data_bloqueio->format('d/m/Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-amber-700">Intervalo de Cobrança</p>
                    <p class="text-sm font-semibold text-amber-950">{{ $billingIntervalLabel }}</p>
                </div>
                <div>
                    <p class="text-xs text-amber-700">Cobrança Automática</p>
                    <p class="text-sm font-semibold text-amber-950">{{ $config->automaticBillingStatusLabel() }}</p>
                </div>
                <div>
                    <p class="text-xs text-amber-700">Valor Unitário</p>
                    <p class="text-sm font-semibold text-amber-950">R$ {{ number_format((float) $config->valor_receber_unitario, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if ($showPixSection)
        <div class="mt-6 rounded-2xl border border-slate-200/80 bg-gradient-to-br from-slate-100 via-white to-blue-50 p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Cobranças PIX</h3>
                    <p class="mt-1 text-sm text-slate-600">Cobranças reais com status sincronizado no Asaas, calculadas sobre os dispositivos ativos da empresa.</p>
                </div>

                @if ($canCreatePixCharge)
                    <div class="w-full max-w-xl rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-white via-emerald-50 to-teal-50 p-5 shadow-sm">
                        @if (! $asaasConfigured)
                            <p class="text-sm text-red-700">Defina <span class="font-semibold">ASAAS_API_KEY</span> no ambiente antes de gerar cobranças PIX.</p>
                        @elseif ($cobrancaAberta)
                            <p class="text-sm text-amber-700">Já existe uma cobrança PIX aberta. Sincronize o status abaixo antes de emitir outra.</p>
                        @else
                            <form method="POST" action="{{ route('admin.financeiro.charges.store', $empresa) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @csrf
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Vencimento</label>
                                    <input
                                        type="date"
                                        name="due_date"
                                        value="{{ old('due_date', $suggestedChargeDueDate) }}"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    >
                                    <p class="mt-1 text-xs text-slate-500">Este é o vencimento da cobrança que será gerada agora. A sugestão vem da data-base do ciclo e do intervalo configurado.</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Descrição</label>
                                    <input
                                        type="text"
                                        name="description"
                                        value="{{ old('description', 'Mensalidade ' . $empresa->nome . ' - ' . now()->format('m/Y')) }}"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    >
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                                        Gerar cobrança PIX
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            @if ($cobrancas->isEmpty())
                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-5 text-sm text-slate-600">
                    Nenhuma cobrança PIX foi gerada ainda para esta empresa.
                </div>
            @else
                <div class="mt-5 space-y-4">
                    @foreach ($cobrancas as $cobranca)
                        @php
                            $qrCodeSrc = null;
                            if ($cobranca->pix_qr_code) {
                                $qrCodeSrc = str_starts_with((string) $cobranca->pix_qr_code, 'data:')
                                    ? $cobranca->pix_qr_code
                                    : 'data:image/png;base64,' . $cobranca->pix_qr_code;
                            }
                        @endphp

                        <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-slate-50 to-indigo-50 p-5 shadow-sm">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-semibold text-slate-900">{{ $cobranca->descricao }}</p>
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $cobranca->statusBadgeClass() }}">{{ $cobranca->statusLabel() }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600">Referência: {{ $cobranca->referencia ?: '-' }} | Vencimento da cobrança: {{ optional($cobranca->vencimento)->format('d/m/Y') ?: '-' }}</p>
                                    <p class="mt-1 text-sm text-slate-600">Dispositivos: {{ $cobranca->quantidade_dispositivos }} | Valor unitário: R$ {{ number_format((float) $cobranca->valor_unitario, 2, ',', '.') }}</p>
                                    <p class="mt-1 text-lg font-bold text-slate-900">R$ {{ number_format((float) $cobranca->valor_total, 2, ',', '.') }}</p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('admin.financeiro.charges.sync', $cobranca) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-md border border-slate-300 bg-white/90 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white">
                                            Atualizar status
                                        </button>
                                    </form>

                                    @if ($cobranca->invoice_url)
                                        <a href="{{ $cobranca->invoice_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-md border border-blue-300 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                            Abrir fatura
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
                                <div class="flex min-h-[220px] items-center justify-center rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-cyan-50 p-4">
                                    @if ($qrCodeSrc)
                                        <img src="{{ $qrCodeSrc }}" alt="QR Code PIX" class="h-44 w-44 rounded-lg border border-slate-200 bg-white p-2">
                                    @else
                                        <p class="text-center text-sm text-slate-500">QR Code ainda não disponível para esta cobrança.</p>
                                    @endif
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pix Copia e Cola</p>
                                        <textarea readonly rows="4" class="mt-1 w-full rounded-xl border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-700">{{ $cobranca->pix_copy_paste }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div class="rounded-xl border border-white/70 bg-white/70 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status Asaas</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $cobranca->status }}</p>
                                        </div>
                                        <div class="rounded-xl border border-white/70 bg-white/70 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Última sincronização</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($cobranca->last_gateway_sync_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                        </div>
                                        <div class="rounded-xl border border-white/70 bg-white/70 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pagamento confirmado em</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($cobranca->paid_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                        </div>
                                        <div class="rounded-xl border border-white/70 bg-white/70 p-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expiração do QR Code</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($cobranca->pix_expires_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>