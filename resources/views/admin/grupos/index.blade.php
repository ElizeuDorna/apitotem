<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-4xl mx-auto rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
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

    <div class="overflow-x-auto rounded-xl border border-slate-300">
        <table class="w-full border-collapse">
            <thead class="bg-blue-100 border-b border-blue-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">NOME</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">DEPARTAMENTO</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-800 uppercase">CNPJ/CPF</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-800 uppercase">PRODUTOS</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-800 uppercase">ACOES</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-300">
                @forelse($grupos as $grupo)
                    <tr class="odd:bg-white even:bg-slate-100 hover:bg-sky-100 transition-colors duration-150">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $grupo->nome }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $grupo->departamento->nome }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $grupo->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $grupo->empresa->cnpj_cpf) : 'Não vinculado' }}</td>
                        <td class="px-4 py-3 text-sm text-center text-slate-700">{{ $grupo->produtos_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.grupos.edit', ['grupo' => $grupo->id, 'return' => url()->full()]) }}" class="inline-flex items-center rounded-full border border-blue-600 bg-blue-500 px-2.5 py-1 text-xs font-semibold text-white">Editar</a>
                            <form action="{{ route('admin.grupos.destroy', $grupo->id) }}" method="POST" class="inline ml-2" onsubmit="return confirm('Tem certeza?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center rounded-full border border-red-600 bg-red-500 px-2.5 py-1 text-xs font-semibold text-white">Deletar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhum grupo cadastrado</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $grupos->links() }}
    </div>
</div>
    </div>
</x-app-layout>
