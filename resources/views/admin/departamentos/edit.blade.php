<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button :href="request()->query('return', route('admin.departamentos.index'))" />
</div>
<div class="max-w-2xl mx-auto bg-white p-8 shadow">
    <h2 class="text-2xl font-bold mb-6">Editar Departamento</h2>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.departamentos.update', $departamento->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-semibold">CNPJ/CPF DA EMPRESA</label>
            <input type="text" value="{{ $departamento->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $departamento->empresa->cnpj_cpf) : 'Não vinculado' }}" class="w-full border rounded px-2 py-1 bg-gray-100" readonly />
        </div>

        <div>
            <label class="block font-semibold">NOME</label>
            <input type="text" name="nome" value="{{ old('nome', $departamento->nome) }}" class="w-full border rounded px-2 py-1 @error('nome') border-red-500 @enderror" required />
            @error('nome')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Atualizar</button>
            <a href="{{ request()->query('return', route('admin.departamentos.index')) }}" class="px-6 py-2 bg-gray-400 text-white rounded">Cancelar</a>
        </div>

    </form>
</div>
    </div>
</x-app-layout>
