<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-3xl mx-auto bg-white p-8 shadow">
    <h2 class="text-2xl font-bold mb-6">Editar Empresa</h2>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.empresas.update', $empresa->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">CÓDIGO</label>
                <input type="text" value="{{ $empresa->codigo }}" class="w-full border rounded px-2 py-1 bg-gray-100 text-gray-600" disabled />
            </div>
            <div>
                <label class="block font-semibold">CNPJ/CPF</label>
                <input type="text" name="cnpj_cpf" value="{{ old('cnpj_cpf', $empresa->cnpj_cpf) }}" class="cnpj-cpf-mask w-full border rounded px-2 py-1 @error('cnpj_cpf') border-red-500 @enderror" maxlength="18" inputmode="numeric" required />
                @error('cnpj_cpf')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block font-semibold">NOME</label>
            <input type="text" name="nome" value="{{ old('nome', $empresa->nome) }}" class="w-full border rounded px-2 py-1 @error('nome') border-red-500 @enderror" required />
            @error('nome')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">RAZÃO SOCIAL</label>
            <input type="text" name="razaosocial" value="{{ old('razaosocial', $empresa->razaosocial) }}" class="w-full border rounded px-2 py-1 @error('razaosocial') border-red-500 @enderror" required />
            @error('razaosocial')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">EMAIL</label>
                <input type="email" name="email" value="{{ old('email', $empresa->email) }}" class="w-full border rounded px-2 py-1 @error('email') border-red-500 @enderror" required />
                @error('email')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-semibold">FONE</label>
                <input type="text" name="fone" value="{{ old('fone', $empresa->fone) }}" class="fone-mask w-full border rounded px-2 py-1 @error('fone') border-red-500 @enderror" maxlength="15" inputmode="numeric" required />
                @error('fone')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block font-semibold">SENHA</label>
            <input type="password" name="password" class="w-full border rounded px-2 py-1 @error('password') border-red-500 @enderror" />
            @if($empresa->password)
                <p class="text-sm text-gray-600 mt-1">Deixe em branco para manter a senha atual</p>
            @else
                <p class="text-sm text-red-600">Obrigatório</p>
            @endif
            @error('password')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        @if($isDefaultAdmin ?? false)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold">NIVEL DE ACESSO</label>
                    <select name="nivel_acesso" id="nivel_acesso" class="w-full border rounded px-2 py-1 @error('nivel_acesso') border-red-500 @enderror" required>
                        <option value="1" @selected((int) old('nivel_acesso', $empresa->nivel_acesso ?? 1) === 1)>Cliente Final (Nivel 1)</option>
                        <option value="2" @selected((int) old('nivel_acesso', $empresa->nivel_acesso ?? 1) === 2)>Revenda (Nivel 2)</option>
                    </select>
                    @error('nivel_acesso')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
                <div id="revenda-wrapper">
                    <label class="block font-semibold">VINCULAR A REVENDA (OPCIONAL)</label>
                    <select name="revenda_id" id="revenda_id" class="w-full border rounded px-2 py-1 @error('revenda_id') border-red-500 @enderror">
                        <option value="">Sem vinculo de revenda</option>
                        @foreach(($revendas ?? collect()) as $revenda)
                            <option value="{{ $revenda->id }}" @selected((string) old('revenda_id', $empresa->revenda_id) === (string) $revenda->id)>
                                {{ $revenda->nome ?: ($revenda->fantasia ?: ('Revenda #' . $revenda->id)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('revenda_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>
            </div>
        @else
            <div>
                <p class="text-sm text-gray-700">
                    Nivel de acesso: {{ (int) ($empresa->nivel_acesso ?? 1) === 2 ? 'Revenda (Nivel 2)' : 'Cliente Final (Nivel 1)' }}
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">ENDEREÇO</label>
                <input type="text" name="endereco" value="{{ old('endereco', $empresa->endereco) }}" class="w-full border rounded px-2 py-1 @error('endereco') border-red-500 @enderror" />
                @error('endereco')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-semibold">BAIRRO</label>
                <input type="text" name="bairro" value="{{ old('bairro', $empresa->bairro) }}" class="w-full border rounded px-2 py-1 @error('bairro') border-red-500 @enderror" />
                @error('bairro')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">NÚMERO</label>
                <input type="text" name="numero" value="{{ old('numero', $empresa->numero) }}" class="w-full border rounded px-2 py-1 @error('numero') border-red-500 @enderror" />
                @error('numero')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-semibold">CEP</label>
                <input type="text" name="cep" value="{{ old('cep', $empresa->cep) }}" class="w-full border rounded px-2 py-1 @error('cep') border-red-500 @enderror" />
                @error('cep')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Atualizar</button>
            <a href="{{ route('admin.empresas.index') }}" class="px-6 py-2 bg-gray-400 text-white rounded">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const formatCpfCnpj = (value) => {
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
    };

    const formatPhone = (value) => {
        const digits = value.replace(/\D/g, '').slice(0, 11);

        if (digits.length <= 10) {
            return digits
                .replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*$/, function (_, d1, d2, d3) {
                    let result = '';
                    if (d1) result += `(${d1}`;
                    if (d1.length === 2) result += ') ';
                    if (d2) result += d2;
                    if (d3) result += '-' + d3;
                    return result;
                });
        }

        return digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*$/, '($1) $2-$3');
    };

    document.querySelectorAll('.fone-mask').forEach((input) => {
        input.value = formatPhone(input.value);
        input.addEventListener('input', (event) => {
            event.target.value = formatPhone(event.target.value);
        });
    });

    document.querySelectorAll('.cnpj-cpf-mask').forEach((input) => {
        input.value = formatCpfCnpj(input.value);
        input.addEventListener('input', (event) => {
            event.target.value = formatCpfCnpj(event.target.value);
        });
    });

    const nivelAcesso = document.getElementById('nivel_acesso');
    const revendaWrapper = document.getElementById('revenda-wrapper');
    const revendaSelect = document.getElementById('revenda_id');

    if (nivelAcesso && revendaWrapper && revendaSelect) {
        const toggleRevendaField = () => {
            const isRevenda = nivelAcesso.value === '2';
            revendaWrapper.classList.toggle('hidden', isRevenda);
            if (isRevenda) {
                revendaSelect.value = '';
            }
        };

        toggleRevendaField();
        nivelAcesso.addEventListener('change', toggleRevendaField);
    }
});
</script>
    </div>
</x-app-layout>
