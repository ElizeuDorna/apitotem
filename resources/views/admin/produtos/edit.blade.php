<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight"></h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <x-back-button :href="request()->query('return', route('admin.produtos.index'))" />
        </div>
        <livewire:admin.product-edit-form :produto="$produto" :return-url="request()->query('return', route('admin.produtos.index'))" />
    </div>
</x-app-layout>
