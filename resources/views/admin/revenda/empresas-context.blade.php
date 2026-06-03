<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Selecionar empresa
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mb-4 px-4">
            <x-back-button />
        </div>

        <div class="mx-auto max-w-6xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-6 flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold">Selecione uma empresa para acessar</h2>
                @if(auth()->user()?->hasMenuAccess('empresas'))
                    <a href="{{ route('admin.empresas.index') }}" class="rounded bg-gray-600 px-4 py-2 text-white">Gerenciar empresas</a>
                @endif
            </div>

            @if($empresaAtivaId)
                @php
                    $empresaAtiva = $empresas->firstWhere('id', $empresaAtivaId);
                @endphp
                <div class="mb-6 rounded border border-indigo-200 bg-indigo-50 p-5">
                    <h3 class="text-xl font-bold text-indigo-900">Empresa atualmente em edição</h3>
                    <p class="mt-2 text-lg text-indigo-800">
                        Você está editando a empresa:
                        <strong>{{ $empresaAtiva?->nome ?? ('Empresa #' . $empresaAtivaId) }}</strong>
                    </p>
                    <p class="mt-2 text-indigo-700">
                        Todas as alterações feitas em Produtos, Departamentos, Grupos, Configurações e demais menus serão aplicadas somente nessa empresa até você trocar a seleção.
                    </p>
                </div>
            @else
                <div class="mb-6 rounded border border-yellow-200 bg-yellow-50 p-5">
                    <h3 class="text-xl font-bold text-yellow-900">Nenhuma empresa selecionada</h3>
                    <p class="mt-2 text-yellow-800">
                        Escolha uma empresa na lista abaixo para começar. Depois de selecionar, todos os menus vão gravar alterações somente na empresa escolhida.
                    </p>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 rounded bg-yellow-100 p-3 text-yellow-800">{{ session('warning') }}</div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div>
            @endif

            <div class="overflow-x-auto rounded-xl border border-slate-300">
                <table class="w-full border-collapse">
                    <thead class="border-b border-blue-200 bg-blue-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">NOME</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">RAZAO SOCIAL</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">CNPJ/CPF</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">EMAIL</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">ACOES</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-300">
                        @forelse($empresas as $empresa)
                            <tr class="odd:bg-white even:bg-slate-100 transition-colors duration-150 hover:bg-sky-100">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $empresa->nome }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->razaosocial }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->cnpj_cpf }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->email }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if((int) $empresaAtivaId === (int) $empresa->id)
                                        <span class="inline-block rounded bg-green-100 px-3 py-1 text-xs text-green-700">Empresa ativa</span>
                                    @endif

                                    <form action="{{ route('admin.revenda.empresas.acessar', $empresa->id) }}" method="POST" class="ml-2 inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-200">Acessar</button>
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
    </div>
</x-app-layout>
