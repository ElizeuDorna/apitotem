<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="mx-auto max-w-6xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <livewire:admin.companies-management-panel />
</div>
    </div>
</x-app-layout>
