<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            
        </h2>
    </x-slot>

    <div class="py-8">
<div class="mb-4 px-4">
    <x-back-button />
</div>
<div class="max-w-4xl mx-auto bg-white p-8 shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Departamentos</h2>
        <a href="{{ route('admin.departamentos.create') }}" class="px-4 py-2 bg-green-600 text-white rounded">+ Novo Departamento</a>
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
                    <th class="px-4 py-2 text-left">CNPJ/CPF</th>
                    <th class="px-4 py-2 text-center">GRUPOS</th>
                    <th class="px-4 py-2 text-center">PRODUTOS</th>
                    <th class="px-4 py-2 text-center">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departamentos as $dept)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $dept->nome }}</td>
                        <td class="px-4 py-2">{{ $dept->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $dept->empresa->cnpj_cpf) : 'Não vinculado' }}</td>
                        <td class="px-4 py-2 text-center">{{ $dept->grupos_count ?? 0 }}</td>
                        <td class="px-4 py-2 text-center">{{ $dept->produtos_count ?? 0 }}</td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <a href="{{ route('admin.departamentos.edit', $dept->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Editar</a>
                            <form action="{{ route('admin.departamentos.destroy', $dept->id) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Deletar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">Nenhum departamento cadastrado</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $departamentos->links() }}
    </div>
</div>
    </div>
</x-app-layout>
