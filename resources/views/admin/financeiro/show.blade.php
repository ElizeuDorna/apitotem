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
            @php
                $canEditConfig = ! ($isAdmin && (int) $empresa->nivel_acesso === \App\Models\Empresa::NIVEL_CLIENTE_FINAL && $empresa->revenda_id !== null);
                $showPixSection = ! ($isEmpresaRevenda ?? false);
            @endphp

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
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dispositivos Ativos</p>
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
                            <p class="text-xs text-slate-500">Data-base do Ciclo</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $config->data_vencimento ? $config->data_vencimento->format('d/m/Y') : '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">Usada como referência para sugerir a próxima cobrança recorrente.</p>
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
                            <p class="text-xs text-slate-500">Intervalo de Cobrança</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $billingIntervalLabel }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Cobrança Automática</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $config->automaticBillingStatusLabel() }}</p>
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
            @elseif ($canEditConfig)
                <form method="POST" action="{{ route('admin.financeiro.update', $empresa->id) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data-base do Ciclo</label>
                            <input type="date" name="data_vencimento" value="{{ old('data_vencimento', optional($config->data_vencimento)->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                            <p class="mt-1 text-xs text-slate-500">Referência usada para sugerir o próximo vencimento recorrente.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data de Aviso</label>
                            <input type="date" name="data_aviso" value="{{ old('data_aviso', optional($config->data_aviso)->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Data de Bloqueio</label>
                            <input type="date" name="data_bloqueio" value="{{ old('data_bloqueio', optional($config->data_bloqueio)->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Intervalo de Cobrança</label>
                            <select name="intervalo_cobranca_dias" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                                @foreach ($billingIntervalOptions as $intervaloDias => $intervaloLabel)
                                    <option value="{{ $intervaloDias }}" @selected((int) old('intervalo_cobranca_dias', $config->billingIntervalDays()) === (int) $intervaloDias)>{{ $intervaloLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <p class="font-semibold text-slate-900">Como o ciclo funciona</p>
                        <p class="mt-2">A <span class="font-medium">data-base do ciclo</span> serve de referência para calcular a próxima cobrança. O <span class="font-medium">vencimento da cobrança</span> é a data real da fatura que será gerada.</p>
                        <p class="mt-2">Exemplos: mensal soma 30 dias, trimestral soma 90 dias, semestral soma 180 dias e anual soma 365 dias a partir da última cobrança gerada ou da data-base configurada.</p>
                        <p class="mt-2">Se a cobrança automática estiver ativada, o sistema verifica diariamente se chegou ao próximo ciclo e gera a cobrança PIX sem ação manual.</p>
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
                        <div>
                            <input type="hidden" name="cobranca_automatica_ativa" value="0">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Cobrança Automática</label>
                            <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="cobranca_automatica_ativa" value="1" @checked(old('cobranca_automatica_ativa', $config->cobranca_automatica_ativa))>
                                <span>Gerar cobrança automaticamente conforme o ciclo configurado</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <input type="hidden" name="asaas_integration_ativa" value="0">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Integração Asaas</label>
                            <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="asaas_integration_ativa" value="1" @checked(old('asaas_integration_ativa', $config->asaas_integration_ativa))>
                                <span>Ativar cobrança e status pelo Asaas para este cliente</span>
                            </label>
                        </div>
                        <div>
                            <input type="hidden" name="bloquear_tv_inadimplencia" value="0">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">Bloqueio da TV</label>
                            <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="bloquear_tv_inadimplencia" value="1" @checked(old('bloquear_tv_inadimplencia', $config->bloquear_tv_inadimplencia))>
                                <span>Bloquear a TV automaticamente quando houver inadimplência</span>
                            </label>
                        </div>
                        <div>
                            <input type="hidden" name="exibir_qr_code_tv_bloqueada" value="0">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-700">QR Code na TV</label>
                            <label class="flex items-center gap-3 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="exibir_qr_code_tv_bloqueada" value="1" @checked(old('exibir_qr_code_tv_bloqueada', $config->exibir_qr_code_tv_bloqueada))>
                                <span>Mostrar QR Code da cobrança na tela bloqueada</span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                        <p class="font-semibold text-sky-950">Como as credenciais do Asaas são escolhidas</p>
                        <p class="mt-2">Cliente final vinculado diretamente ao admin usa as credenciais globais salvas em Config Admin.</p>
                        <p class="mt-2">Cliente final vinculado a uma revenda usa as credenciais da própria revenda em Config Admin.</p>
                        <p class="mt-2">Se a integração estiver desativada para este cliente, o sistema não gera cobrança nem usa status do Asaas para bloquear ou liberar a TV.</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Salvar configuração financeira
                        </button>
                    </div>
                </form>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
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
                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Cobranças PIX</h3>
                            <p class="mt-1 text-sm text-slate-600">Cobranças reais com status sincronizado no Asaas, calculadas sobre os dispositivos ativos da empresa.</p>
                        </div>

                        @if ($canCreatePixCharge)
                            <div class="w-full max-w-xl rounded-xl border border-slate-200 bg-white p-4">
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
                        <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-600">
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

                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
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
                                                <button type="submit" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
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
                                        <div class="flex min-h-[220px] items-center justify-center rounded-lg border border-slate-200 bg-slate-50 p-4">
                                            @if ($qrCodeSrc)
                                                <img src="{{ $qrCodeSrc }}" alt="QR Code PIX" class="h-44 w-44 rounded-lg border border-slate-200 bg-white p-2">
                                            @else
                                                <p class="text-center text-sm text-slate-500">QR Code ainda não disponível para esta cobrança.</p>
                                            @endif
                                        </div>
                                        <div class="space-y-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pix Copia e Cola</p>
                                                <textarea readonly rows="4" class="mt-1 w-full rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $cobranca->pix_copy_paste }}</textarea>
                                            </div>
                                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status Asaas</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $cobranca->status }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Última sincronização</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($cobranca->last_gateway_sync_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pagamento confirmado em</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($cobranca->paid_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                                </div>
                                                <div>
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
    </div>
</x-app-layout>
