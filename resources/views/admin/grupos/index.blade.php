<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-4xl mx-auto rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Grupos</h2>
        <button
            type="button"
            x-data="{}"
            x-on:click="$dispatch('groups-create'); document.getElementById('create-grupo')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
            class="rounded bg-green-600 px-4 py-2 text-white"
        >
            + Novo Grupo
        </button>
    </div>

    <livewire:admin.groups-management-panel />
</div>
    </div>
</x-app-layout>
