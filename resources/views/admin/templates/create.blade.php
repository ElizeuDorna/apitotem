<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Template</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.templates.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input name="nome" type="text" value="{{ old('nome') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de layout</label>
                            <select name="tipo_layout" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach($layouts as $layout)
                                    <option value="{{ $layout }}">{{ $layout }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($isDefaultAdmin)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Empresa</label>
                                <select name="empresa_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="">Selecione</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" @selected((string) old('empresa_id') === (string) $empresa->id)>
                                            {{ $empresa->NOME }} - {{ $empresa->CNPJ_CPF }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('empresa_id')" class="mt-2" />
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <a href="{{ route('admin.templates.index') }}" class="text-sm text-gray-600 underline">Voltar</a>
                            <x-primary-button>Criar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
