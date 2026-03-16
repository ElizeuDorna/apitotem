<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-2xl mx-auto bg-white p-8 shadow">
    <h2 class="text-2xl font-bold mb-6">Editar Grupo</h2>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.grupos.update', $grupo->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-semibold">CNPJ/CPF DA EMPRESA</label>
            <input type="text" id="empresa_cnpj_cpf" value="" class="w-full border rounded px-2 py-1 bg-gray-100" readonly />
        </div>

        <div>
            <label class="block font-semibold">NOME</label>
            <input type="text" name="nome" value="{{ old('nome', $grupo->nome) }}" class="w-full border rounded px-2 py-1 @error('nome') border-red-500 @enderror" required />
            @error('nome')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">DEPARTAMENTO</label>
            <select id="departamento_id" name="departamento_id" class="w-full border rounded px-2 py-1 @error('departamento_id') border-red-500 @enderror" required>
                <option value="">-- Selecione --</option>
                @foreach($departamentos as $dept)
                    <option value="{{ $dept->id }}" data-cnpj="{{ $dept->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $dept->empresa->cnpj_cpf) : 'Não vinculado' }}" {{ old('departamento_id', $grupo->departamento_id) == $dept->id ? 'selected' : '' }}>
                        {{ $dept->nome }} ({{ $dept->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $dept->empresa->cnpj_cpf) : 'Sem CNPJ/CPF' }})
                    </option>
                @endforeach
            </select>
            @error('departamento_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Atualizar</button>
            <a href="{{ route('admin.grupos.index') }}" class="px-6 py-2 bg-gray-400 text-white rounded">Cancelar</a>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const departamentoSelect = document.getElementById('departamento_id');
    const cnpjInput = document.getElementById('empresa_cnpj_cpf');

    function updateEmpresaCnpj() {
        const selected = departamentoSelect.options[departamentoSelect.selectedIndex];
        cnpjInput.value = selected && selected.value
            ? (selected.dataset.cnpj || 'Não vinculado')
            : 'Selecione um departamento';
    }

    departamentoSelect.addEventListener('change', updateEmpresaCnpj);
    updateEmpresaCnpj();
});
</script>
    </div>
</x-app-layout>
