<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-6xl mx-auto rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-900">Empresas</h2>
        <a href="{{ route('admin.empresas.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg shadow-sm hover:bg-green-700 transition">+ Nova Empresa</a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    @if(($podePesquisar ?? false))
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50/70 p-4">
            <p class="text-sm font-semibold text-blue-900">Pesquisar empresa</p>
            <p class="mt-1 text-xs text-blue-700">Digite nome ou CNPJ e clique em Pesquisar para filtrar a grade.</p>

            @if((int) ($empresaAtivaId ?? 0) > 0)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                    <span>Empresa ativa: <strong>{{ $empresaAtivaNome ?? ('ID #' . $empresaAtivaId) }}</strong></span>
                    <form method="POST" action="{{ route('admin.empresas.limpar-selecao', request()->query()) }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">Limpar selecao</button>
                    </form>
                </div>
            @endif

            <form method="GET" action="{{ route('admin.empresas.index') }}" class="mt-3 flex flex-col gap-3 md:flex-row md:items-end">
                <div class="w-full md:flex-1">
                    <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-blue-900">Nome ou CNPJ</label>
                    <input
                        id="q"
                        name="q"
                        type="text"
                        value="{{ $buscaEmpresa ?? '' }}"
                        class="w-full rounded-md border border-blue-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Ex: Mercado Alfa ou 12.345.678/0001-90"
                    >
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Pesquisar</button>
                    @if(($buscaEmpresa ?? '') !== '')
                        <a href="{{ route('admin.empresas.index') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpar</a>
                    @endif
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-slate-300">
        <table class="w-full border-collapse">
            <thead class="bg-blue-100 border-b border-blue-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">CODIGO</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">NOME</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">NIVEL</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">REVENDA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">RAZAO SOCIAL</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">CNPJ/CPF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">EMAIL</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">FONE</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-800 uppercase">ACOES</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-300">
                @forelse($empresas as $empresa)
                    @php
                        $userLogado = auth()->user();
                        $isAdminOuRevenda = $userLogado->isDefaultAdmin() || ($userLogado->empresa && $userLogado->empresa->isRevenda());
                        $revendaId = $userLogado->empresa?->id;
                        $podeSelecionarLinha = $isAdminOuRevenda
                            && ($userLogado->isDefaultAdmin() || (int) ($empresa->revenda_id ?? 0) === (int) $revendaId);
                        $isEmpresaAtivaLinha = (int) ($empresaAtivaId ?? 0) === (int) $empresa->id;
                    @endphp
                    <tr
                        class="odd:bg-white even:bg-slate-100 transition-colors duration-150 {{ $isEmpresaAtivaLinha ? 'bg-emerald-100/80 hover:bg-emerald-200/70' : 'hover:bg-sky-100' }} {{ $podeSelecionarLinha ? 'cursor-pointer' : '' }}"
                        @if($podeSelecionarLinha)
                            onclick="if (!event.target.closest('[data-actions]')) { window.location.href='{{ route('admin.empresas.selecionar.get', ['empresa' => $empresa->id] + request()->query()) }}'; }"
                        @endif
                    >
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->codigo }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $empresa->nome }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ (int) ($empresa->nivel_acesso ?? 1) === 2 ? 'Revenda (N2)' : 'Cliente Final (N1)' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->revenda?->nome ?: ($empresa->revenda?->fantasia ?: '-') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->razaosocial }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->cnpj_cpf }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->email }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->fone }}</td>
                        <td class="px-4 py-3 text-center" data-actions="true">
                            <a href="{{ route('admin.empresas.edit', $empresa->id) }}" class="inline-flex items-center rounded-full border border-blue-600 bg-blue-500 px-2.5 py-1 text-xs font-semibold text-white">Editar</a>
                            <form action="{{ route('admin.empresas.destroy', $empresa->id) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Tem certeza?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center rounded-full border border-red-600 bg-red-500 px-2.5 py-1 text-xs font-semibold text-white">Deletar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                            @if(($buscaEmpresa ?? '') !== '')
                                Nenhuma empresa encontrada para a pesquisa.
                            @else
                                Nenhuma empresa cadastrada
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $empresas->links() }}
    </div>
</div>
    </div>
</x-app-layout>
