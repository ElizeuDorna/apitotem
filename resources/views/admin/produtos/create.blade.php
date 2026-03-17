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
    <h2 class="text-2xl font-bold mb-6">Criar Novo Produto</h2>

    @if(auth()->user()->isDefaultAdmin() && (int) ($empresaSelecionadaAdmin ?? 0) > 0)
        <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
            Cadastro vinculado a empresa selecionada na listagem.
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.produtos.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block font-semibold">CÓDIGO (14 caracteres)</label>
            <input type="text" name="CODIGO" maxlength="14" value="{{ old('CODIGO') }}" class="w-full border rounded px-2 py-1 @error('CODIGO') border-red-500 @enderror" />
            @error('CODIGO')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">NOME</label>
            <input type="text" name="NOME" value="{{ old('NOME') }}" class="w-full border rounded px-2 py-1 @error('NOME') border-red-500 @enderror" required />
            @error('NOME')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">CNPJ/CPF</label>
            <input type="text" value="{{ old('cnpj_cpf', $cnpjCpfEmpresa) }}" class="w-full border rounded px-2 py-1 bg-gray-100" readonly />
            <input type="hidden" name="cnpj_cpf" value="{{ old('cnpj_cpf', $cnpjCpfEmpresa) }}" />
            @error('cnpj_cpf')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">PREÇO</label>
                <input type="number" step="0.01" name="PRECO" value="{{ old('PRECO') }}" class="w-full border rounded px-2 py-1 @error('PRECO') border-red-500 @enderror" required />
                @error('PRECO')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold">OFERTA (opcional)</label>
                <input type="number" step="0.01" name="OFERTA" value="{{ old('OFERTA', 0) }}" class="w-full border rounded px-2 py-1" />
                @error('OFERTA')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block font-semibold">IMAGEM (URL)</label>
            <input type="url" name="IMG" value="{{ old('IMG') }}" class="w-full border rounded px-2 py-1" />
            @error('IMG')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">DEPARTAMENTO</label>
            <select name="departamento_id" class="w-full border rounded px-2 py-1 @error('departamento_id') border-red-500 @enderror" required onchange="updateGrupos()">
                <option value="">-- Selecione --</option>
                @foreach($departamentos as $dept)
                    <option value="{{ $dept->id }}" {{ old('departamento_id') == $dept->id ? 'selected' : '' }}>{{ $dept->nome }}</option>
                @endforeach
            </select>
            @error('departamento_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">GRUPO</label>
            <select name="grupo_id" id="grupo_id" class="w-full border rounded px-2 py-1 @error('grupo_id') border-red-500 @enderror" required>
                <option value="">-- Selecione departamento primeiro --</option>
                @if(old('departamento_id'))
                    @foreach($grupos->where('departamento_id', old('departamento_id')) as $grupo)
                        <option value="{{ $grupo->id }}" {{ old('grupo_id') == $grupo->id ? 'selected' : '' }}>{{ $grupo->nome }}</option>
                    @endforeach
                @endif
            </select>
            @error('grupo_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Salvar</button>
            <a href="{{ route('admin.produtos.index') }}" class="px-6 py-2 bg-gray-400 text-white rounded">Cancelar</a>
        </div>

    </form>
</div>

<script>
const gruposDisponiveis = @json($grupos->map(fn($grupo) => ['id' => $grupo->id, 'nome' => $grupo->nome, 'departamento_id' => $grupo->departamento_id])->values());

function updateGrupos() {
    const deptId = document.querySelector('select[name="departamento_id"]').value;
    const grupoSelect = document.getElementById('grupo_id');
    const selectedGrupoId = '{{ old('grupo_id') }}';
    
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

updateGrupos();
</script>
    </div>
</x-app-layout>
