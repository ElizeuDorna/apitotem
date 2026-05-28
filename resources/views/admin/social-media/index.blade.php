<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rede Social</h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <x-back-button />
        </div>

        <div class="mb-6 px-4">
            <div class="inline-flex rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
                <a
                    href="{{ route('admin.social-media.index') }}"
                    class="rounded-xl px-4 py-2 text-sm font-semibold {{ ($activeTab ?? 'social') === 'social' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Instagram e Facebook
                </a>
                <a
                    href="{{ route('admin.social-media.whatsapp.index') }}"
                    class="rounded-xl px-4 py-2 text-sm font-semibold {{ ($activeTab ?? 'social') === 'whatsapp' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    WhatsApp
                </a>
            </div>
        </div>

        @if (($activeTab ?? 'social') === 'whatsapp')
            <livewire:admin.whats-app-campaigns-panel />
        @else
            <livewire:admin.social-media-templates-panel />
        @endif
    </div>
</x-app-layout>