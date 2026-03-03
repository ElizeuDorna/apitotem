@extends('home')

@section('title', 'Admin - Empresas')

@section('content')
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-6xl mx-auto bg-white p-8 shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Empresas</h2>
        <a href="{{ route('admin.empresas.create') }}" class="px-4 py-2 bg-green-600 text-white rounded">+ Nova Empresa</a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">CÓDIGO</th>
                    <th class="px-4 py-2 text-left">NOME</th>
                    <th class="px-4 py-2 text-left">RAZÃO SOCIAL</th>
                    <th class="px-4 py-2 text-left">CNPJ/CPF</th>
                    <th class="px-4 py-2 text-left">EMAIL</th>
                    <th class="px-4 py-2 text-left">FONE</th>
                    <th class="px-4 py-2 text-center">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($empresas as $empresa)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $empresa->codigo }}</td>
                        <td class="px-4 py-2">{{ $empresa->nome }}</td>
                        <td class="px-4 py-2">{{ $empresa->razaosocial }}</td>
                        <td class="px-4 py-2">{{ $empresa->cnpj_cpf }}</td>
                        <td class="px-4 py-2">{{ $empresa->email }}</td>
                        <td class="px-4 py-2">{{ $empresa->fone }}</td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <a href="{{ route('admin.empresas.edit', $empresa->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                            <form action="{{ route('admin.empresas.destroy', $empresa->id) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Deletar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">Nenhuma empresa cadastrada</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $empresas->links() }}
    </div>
</div>
@endsection
