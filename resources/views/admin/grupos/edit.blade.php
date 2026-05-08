<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button :href="request()->query('return', route('admin.grupos.index'))" />
</div>
<livewire:admin.group-edit-form :grupo="$grupo" :return-url="request()->query('return', route('admin.grupos.index'))" />
    </div>
</x-app-layout>
