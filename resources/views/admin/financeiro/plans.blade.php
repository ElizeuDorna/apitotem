<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Financeiro - Planos Self-service
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <a href="{{ route('admin.financeiro.index') }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Voltar para financeiro
            </a>
        </div>

        <div class="max-w-7xl mx-auto px-4">
            <livewire:admin.subscription-plans-management-panel />
        </div>
    </div>
</x-app-layout>