@extends('home')

@section('title', 'Revenda - Selecionar Empresa')

@section('content')
<div class="mb-4 px-4">
    <x-back-button />
</div>

<div class="max-w-6xl mx-auto bg-white p-8 shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Selecione uma empresa para acessar</h2>
        <a href="{{ route('admin.empresas.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded">Gerenciar empresas</a>
    </div>

    @if($empresaAtivaId)
        @php
            $empresaAtiva = $empresas->firstWhere('id', $empresaAtivaId);
        @endphp
        <div class="mb-6 p-5 bg-indigo-50 border border-indigo-200 rounded">
            <h3 class="text-xl font-bold text-indigo-900">Empresa atualmente em edição</h3>
            <p class="mt-2 text-indigo-800 text-lg">
                Você está editando a empresa:
                <strong>{{ $empresaAtiva?->nome ?? ('Empresa #' . $empresaAtivaId) }}</strong>
            </p>
            <p class="mt-2 text-indigo-700">
                Todas as alterações feitas em Produtos, Departamentos, Grupos, Configurações e demais menus serão aplicadas somente nessa empresa até você trocar a seleção.
            </p>
        </div>
    @else
        <div class="mb-6 p-5 bg-yellow-50 border border-yellow-200 rounded">
            <h3 class="text-xl font-bold text-yellow-900">Nenhuma empresa selecionada</h3>
            <p class="mt-2 text-yellow-800">
                Escolha uma empresa na lista abaixo para começar. Depois de selecionar, todos os menus vão gravar alterações somente na empresa escolhida.
            </p>
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-4 p-3 bg-yellow-100 text-yellow-800 rounded">{{ session('warning') }}</div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">NOME</th>
                    <th class="px-4 py-2 text-left">RAZÃO SOCIAL</th>
                    <th class="px-4 py-2 text-left">CNPJ/CPF</th>
                    <th class="px-4 py-2 text-left">EMAIL</th>
                    <th class="px-4 py-2 text-center">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($empresas as $empresa)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $empresa->nome }}</td>
                        <td class="px-4 py-2">{{ $empresa->razaosocial }}</td>
                        <td class="px-4 py-2">{{ $empresa->cnpj_cpf }}</td>
                        <td class="px-4 py-2">{{ $empresa->email }}</td>
                        <td class="px-4 py-2 text-center">
                            @if((int) $empresaAtivaId === (int) $empresa->id)
                                <span class="inline-block px-3 py-1 text-xs bg-green-100 text-green-700 rounded">Empresa ativa</span>
                            @endif

                            <form action="{{ route('admin.revenda.empresas.acessar', $empresa->id) }}" method="POST" class="inline ml-2">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Acessar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">Nenhuma empresa cliente cadastrada para esta revenda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
