<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Grupos
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <x-back-button />
        </div>

        <div class="mx-auto max-w-4xl px-4">
            <livewire:admin.groups-management-panel />
        </div>
    </div>
</x-app-layout>
