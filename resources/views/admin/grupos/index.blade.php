@extends('home')

@section('title', 'Admin - Grupos')

@section('content')
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-4xl mx-auto bg-white p-8 shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Grupos</h2>
        <a href="{{ route('admin.grupos.create') }}" class="px-4 py-2 bg-green-600 text-white rounded">+ Novo Grupo</a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif
    @if($errors->has('delete'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ $errors->first('delete') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">NOME</th>
                    <th class="px-4 py-2 text-left">DEPARTAMENTO</th>
                    <th class="px-4 py-2 text-left">CNPJ/CPF</th>
                    <th class="px-4 py-2 text-center">PRODUTOS</th>
                    <th class="px-4 py-2 text-center">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grupos as $grupo)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $grupo->nome }}</td>
                        <td class="px-4 py-2">{{ $grupo->departamento->nome }}</td>
                        <td class="px-4 py-2">{{ $grupo->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $grupo->empresa->cnpj_cpf) : 'Não vinculado' }}</td>
                        <td class="px-4 py-2 text-center">{{ $grupo->produtos_count ?? 0 }}</td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <a href="{{ route('admin.grupos.edit', $grupo->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                            <form action="{{ route('admin.grupos.destroy', $grupo->id) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Deletar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">Nenhum grupo cadastrado</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $grupos->links() }}
    </div>
</div>
@endsection
