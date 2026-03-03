@extends('home')

@section('title', 'Admin - Editar Usuário')

@section('content')
<div class="mb-4 px-4">
    <x-back-button />
</div>

<div class="max-w-3xl mx-auto bg-white p-8 shadow">
    <h2 class="text-2xl font-bold mb-6">Editar Usuário</h2>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.users.update', $user) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">NOME</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border rounded px-2 py-1 @error('name') border-red-500 @enderror" required autofocus autocomplete="name" />
                @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold">EMAIL</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border rounded px-2 py-1 @error('email') border-red-500 @enderror" required autocomplete="username" />
                @error('email')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block font-semibold">CPF/CNPJ</label>
            <input id="cpf" type="text" name="cpf" value="{{ old('cpf', $user->cpf) }}" class="cpf-cnpj-mask w-full border rounded px-2 py-1 @error('cpf') border-red-500 @enderror" required maxlength="18" inputmode="numeric" />
            @error('cpf')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        @if (($requiresEmpresaVinculo ?? false))
            <div>
                <label class="block font-semibold">EMPRESA</label>
                <select id="empresa_id" name="empresa_id" class="w-full border rounded px-2 py-1 @error('empresa_id') border-red-500 @enderror" required>
                    <option value="">Selecione uma empresa</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected(old('empresa_id', $user->empresa_id) == $empresa->id)>
                            {{ $empresa->fantasia ?: ($empresa->razaosocial ?: ('Empresa #' . $empresa->id)) }}
                        </option>
                    @endforeach
                </select>
                @error('empresa_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">NOVA SENHA (OPCIONAL)</label>
                <input id="password" type="password" name="password" class="w-full border rounded px-2 py-1 @error('password') border-red-500 @enderror" autocomplete="new-password" />
                @error('password')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold">CONFIRMAR NOVA SENHA</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="w-full border rounded px-2 py-1 @error('password_confirmation') border-red-500 @enderror" autocomplete="new-password" />
                @error('password_confirmation')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">Salvar alterações</button>
            <a href="{{ route('register') }}" class="px-6 py-2 bg-gray-400 text-white rounded">Voltar</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    document.querySelectorAll('.cpf-cnpj-mask').forEach((input) => {
        input.value = formatCpfCnpj(input.value);
        input.addEventListener('input', (event) => {
            event.target.value = formatCpfCnpj(event.target.value);
        });
    });

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const cpfInput = document.getElementById('cpf');
            cpfInput.value = cpfInput.value.replace(/\D/g, '');
        });
    }
});
</script>
@endsection
