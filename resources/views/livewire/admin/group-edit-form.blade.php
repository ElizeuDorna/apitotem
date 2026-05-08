<div class="max-w-2xl mx-auto bg-white p-8 shadow">
    <h2 class="mb-6 text-2xl font-bold">Editar Grupo</h2>

    @if ($statusMessage)
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-green-800">
            {{ $statusMessage }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block font-semibold">CNPJ/CPF DA EMPRESA</label>
            <input
                type="text"
                value="{{ optional($departamentos->firstWhere('id', (int) $departamentoId)?->empresa)->cnpj_cpf ? preg_replace('/\D/', '', optional($departamentos->firstWhere('id', (int) $departamentoId)?->empresa)->cnpj_cpf) : 'Selecione um departamento' }}"
                class="w-full rounded border bg-gray-100 px-2 py-1"
                readonly
            />
        </div>

        <div>
            <label class="block font-semibold">NOME</label>
            <input type="text" wire:model="nome" class="w-full rounded border px-2 py-1 @error('nome') border-red-500 @enderror" required />
            @error('nome')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">DEPARTAMENTO</label>
            <select wire:model="departamentoId" class="w-full rounded border px-2 py-1 @error('departamento_id') border-red-500 @enderror" required>
                <option value="">-- Selecione --</option>
                @foreach($departamentos as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->nome }} ({{ $dept->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $dept->empresa->cnpj_cpf) : 'Sem CNPJ/CPF' }})</option>
                @endforeach
            </select>
            @error('departamento_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded bg-indigo-600 px-6 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60">Atualizar</button>
            <a href="{{ $returnUrl }}" class="rounded bg-gray-400 px-6 py-2 text-white">Cancelar</a>
        </div>
    </form>
</div>