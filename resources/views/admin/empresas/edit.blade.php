<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
    <livewire:admin.company-edit-form :empresa="$empresa" :return-url="$returnUrl" />
    </div>
</x-app-layout>
