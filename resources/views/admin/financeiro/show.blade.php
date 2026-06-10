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

        <livewire:admin.financeiro-show-panel
            :empresa="$empresa"
            :config="$config"
            :quantidade-dispositivos="$quantidadeDispositivos"
            :total-pagar="$totalPagar"
            :total-receber="$totalReceber"
            :is-admin="$isAdmin"
            :is-revenda="$isRevenda"
            :is-cliente-final="$isClienteFinal"
            :is-empresa-revenda="$isEmpresaRevenda"
            :cobrancas="$cobrancas"
            :cobranca-aberta="$cobrancaAberta"
            :can-create-pix-charge="$canCreatePixCharge"
            :asaas-configured="$asaasConfigured"
            :billing-interval-options="$billingIntervalOptions"
            :billing-interval-label="$billingIntervalLabel"
            :suggested-charge-due-date="$suggestedChargeDueDate"
        />
    </div>
</x-app-layout>
