<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ ($activeTab ?? 'social') === 'whatsapp' ? 'WhatsApp' : 'Instagram e Facebook' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <x-back-button />
        </div>

        @if (($activeTab ?? 'social') === 'whatsapp')
            <livewire:admin.whats-app-campaigns-panel />
        @else
            <livewire:admin.social-media-templates-panel />
        @endif
    </div>
</x-app-layout>