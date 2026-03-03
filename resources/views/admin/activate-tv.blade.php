<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ativar TV
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-5">
                    @if (session('success'))
                        <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($activatedToken)
                        <div class="rounded-md bg-blue-50 p-4">
                            <p class="text-xs text-blue-700">Token gerado para a TV</p>
                            <textarea rows="3" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $activatedToken }}</textarea>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.activate-device') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">Código mostrado na TV</label>
                            <input id="code" name="code" type="text" maxlength="5" value="{{ old('code') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        @if ($isDefaultAdmin)
                            <div>
                                <label for="empresa_id" class="block text-sm font-medium text-gray-700">Empresa</label>
                                <select id="empresa_id" name="empresa_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Selecione</option>
                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" @selected((string) old('empresa_id') === (string) $empresa->id)>
                                            {{ $empresa->NOME }} - {{ $empresa->CNPJ_CPF }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('empresa_id')" class="mt-2" />
                            </div>
                        @else
                            <div class="rounded-md bg-gray-50 p-3 text-sm text-gray-700">
                                Empresa vinculada: <strong>{{ $empresaVinculada?->NOME }}</strong>
                                ({{ $empresaVinculada?->CNPJ_CPF }})
                            </div>
                        @endif

                        <div>
                            <label for="nome_tv" class="block text-sm font-medium text-gray-700">Nome da TV</label>
                            <input id="nome_tv" name="nome_tv" type="text" value="{{ old('nome_tv') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <x-input-error :messages="$errors->get('nome_tv')" class="mt-2" />
                        </div>

                        <div>
                            <label for="local" class="block text-sm font-medium text-gray-700">Local/Setor</label>
                            <input id="local" name="local" type="text" value="{{ old('local') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('local')" class="mt-2" />
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>
                                Ativar
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
