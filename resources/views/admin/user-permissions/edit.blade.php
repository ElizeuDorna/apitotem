<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Permissões de Acesso - {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.user-permissions.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div>
                            <p class="text-sm text-gray-600">Selecione os menus que este usuário pode acessar.</p>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($menuOptions as $menuKey => $menuLabel)
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        name="menu_permissions[]"
                                        value="{{ $menuKey }}"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        @checked(in_array($menuKey, old('menu_permissions', $user->menu_permissions ?? []), true))
                                    >
                                    <span class="ms-2 text-sm text-gray-700">{{ $menuLabel }}</span>
                                </label>
                            @endforeach
                        </div>

                        <x-input-error :messages="$errors->get('menu_permissions')" class="mt-2" />

                        <div class="mt-6 flex items-center justify-between">
                            <a href="{{ route('admin.user-permissions.index') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Voltar
                            </a>

                            <x-primary-button>
                                Salvar permissões
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
