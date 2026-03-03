<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestão de TVs
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('success') }}</div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">TV</th>
                                    <th class="px-3 py-2 text-left">Empresa</th>
                                    <th class="px-3 py-2 text-left">Template</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-left">Última comunicação</th>
                                    <th class="px-3 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($devices as $device)
                                    @php
                                        $isOnline = $device->last_seen_at && $device->last_seen_at->gt(now()->subMinutes(2));
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">
                                            <div class="font-medium">{{ $device->nome }}</div>
                                            <div class="text-gray-500">{{ $device->local }}</div>
                                        </td>
                                        <td class="px-3 py-2">{{ $device->empresa?->NOME }}</td>
                                        <td class="px-3 py-2">{{ $device->configuration?->template?->nome ?? 'Sem template' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $isOnline ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $isOnline ? 'Online' : 'Offline' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2">{{ $device->last_seen_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('admin.devices.edit', $device) }}" class="text-indigo-600 hover:text-indigo-800">Editar</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">Nenhuma TV encontrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $devices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
