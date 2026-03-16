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
    <h2 class="text-2xl font-bold mb-6">Editar Produto</h2>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.produtos.update', ['produto' => $produto->id]) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-semibold">CÓDIGO (14 caracteres)</label>
            <input type="text" name="CODIGO" maxlength="14" value="{{ old('CODIGO', $produto->CODIGO) }}" class="w-full border rounded px-2 py-1 @error('CODIGO') border-red-500 @enderror" />
            @error('CODIGO')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">NOME</label>
            <input type="text" name="NOME" value="{{ old('NOME', $produto->NOME) }}" class="w-full border rounded px-2 py-1 @error('NOME') border-red-500 @enderror" required />
            @error('NOME')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">CNPJ/CPF</label>
            <input type="text" name="cnpj_cpf" value="{{ old('cnpj_cpf', $produto->cnpj_cpf) }}" class="cnpj-cpf-mask w-full border rounded px-2 py-1 @error('cnpj_cpf') border-red-500 @enderror" maxlength="18" inputmode="numeric" required />
            @error('cnpj_cpf')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">PREÇO</label>
                <input type="number" step="0.01" name="PRECO" value="{{ old('PRECO', $produto->PRECO) }}" class="w-full border rounded px-2 py-1 @error('PRECO') border-red-500 @enderror" required />
                @error('PRECO')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold">OFERTA (opcional)</label>
                <input type="number" step="0.01" name="OFERTA" value="{{ old('OFERTA', $produto->OFERTA ?? 0) }}" class="w-full border rounded px-2 py-1" />
                @error('OFERTA')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block font-semibold">IMAGEM (URL)</label>
            <input type="url" name="IMG" value="{{ old('IMG', $produto->IMG) }}" class="w-full border rounded px-2 py-1" />
            @error('IMG')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">DEPARTAMENTO</label>
            <select name="departamento_id" class="w-full border rounded px-2 py-1 @error('departamento_id') border-red-500 @enderror" required onchange="updateGrupos()">
                <option value="">-- Selecione --</option>
                @foreach($departamentos as $dept)
                    <option value="{{ $dept->id }}" {{ old('departamento_id', $produto->departamento_id) == $dept->id ? 'selected' : '' }}>{{ $dept->nome }}</option>
                @endforeach
            </select>
            @error('departamento_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">GRUPO</label>
            <select name="grupo_id" id="grupo_id" class="w-full border rounded px-2 py-1 @error('grupo_id') border-red-500 @enderror" required>
                <option value="">-- Selecione --</option>
                @foreach($grupos as $grupo)
                    <option value="{{ $grupo->id }}" {{ old('grupo_id', $produto->grupo_id) == $grupo->id ? 'selected' : '' }}>{{ $grupo->nome }}</option>
                @endforeach
            </select>
            @error('grupo_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Atualizar</button>
            <a href="{{ route('admin.produtos.index') }}" class="px-6 py-2 bg-gray-400 text-white rounded">Cancelar</a>
        </div>

    </form>
</div>

<script>
const gruposDisponiveis = @json($grupos->map(fn($grupo) => ['id' => $grupo->id, 'nome' => $grupo->nome, 'departamento_id' => $grupo->departamento_id])->values());

function updateGrupos() {
    const deptId = document.querySelector('select[name="departamento_id"]').value;
    const grupoSelect = document.getElementById('grupo_id');
    const selectedGrupoId = "{{ old('grupo_id', $produto->grupo_id) }}";
    
    if (!deptId) {
        grupoSelect.innerHTML = '<option value="">-- Selecione departamento primeiro --</option>';
        return;
    }

    const gruposFiltrados = gruposDisponiveis.filter((grupo) => String(grupo.departamento_id) === String(deptId));

    grupoSelect.innerHTML = '<option value="">-- Selecione --</option>';
    gruposFiltrados.forEach((grupo) => {
        const option = document.createElement('option');
        option.value = grupo.id;
        option.textContent = grupo.nome;
        if (String(selectedGrupoId) === String(grupo.id)) {
            option.selected = true;
        }
        grupoSelect.appendChild(option);
    });
}

function formatCpfCnpj(value) {
    const digits = value.replace(/\D/g, '').slice(0, 14);

    if (digits.length <= 11) {
        return digits
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}

document.querySelectorAll('.cnpj-cpf-mask').forEach((input) => {
    input.value = formatCpfCnpj(input.value);
    input.addEventListener('input', (event) => {
        event.target.value = formatCpfCnpj(event.target.value);
    });
});

updateGrupos();
</script>
    </div>
</x-app-layout>
