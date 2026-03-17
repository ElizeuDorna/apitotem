@extends('home')

@section('title', 'Revenda - Selecionar Empresa')

@section('content')
<div class="mb-4 px-4">
    <x-back-button />
</div>

<div class="max-w-6xl mx-auto rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
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

    <div class="overflow-x-auto rounded-xl border border-slate-300">
        <table class="w-full border-collapse">
            <thead class="bg-blue-100 border-b border-blue-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">NOME</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">RAZAO SOCIAL</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">CNPJ/CPF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">EMAIL</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-800 uppercase">ACOES</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-300">
                @forelse($empresas as $empresa)
                    <tr class="odd:bg-white even:bg-slate-100 hover:bg-sky-100 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $empresa->nome }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->razaosocial }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->cnpj_cpf }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->email }}</td>
                        <td class="px-4 py-3 text-center">
                            @if((int) $empresaAtivaId === (int) $empresa->id)
                                <span class="inline-block px-3 py-1 text-xs bg-green-100 text-green-700 rounded">Empresa ativa</span>
                            @endif

                            <form action="{{ route('admin.revenda.empresas.acessar', $empresa->id) }}" method="POST" class="inline ml-2">
                                @csrf
                                <button type="submit" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-200 transition">Acessar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhuma empresa cliente cadastrada para esta revenda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
