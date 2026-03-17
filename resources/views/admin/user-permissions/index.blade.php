<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Permissões de Acesso
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            @if (! ($menuPermissionsReady ?? false))
                <div class="mb-4 p-3 bg-yellow-100 text-yellow-900 rounded">
                    A coluna de permissões ainda não existe no banco. Rode: <strong>php artisan migrate</strong>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto border border-gray-200 rounded-md">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF/CNPJ</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nivel da Empresa</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->name }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->email }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $user->cpf }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">
                                            @if ($user->empresa)
                                                {{ $user->empresa->isRevenda() ? 'Revenda (N2)' : 'Cliente Final (N1)' }}
                                            @else
                                                Sem empresa
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <a href="{{ route('admin.user-permissions.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Configurar menus</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-sm text-gray-500 text-center">Nenhum usuário encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
