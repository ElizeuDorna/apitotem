<div class="space-y-6">
    @if (($statusMessage ?? null) || session('success'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ $statusMessage ?? session('success') }}
        </div>
    @endif

    @if($errors->has('delete'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('delete') }}
        </div>
    @endif

    <div id="create-grupo" class="rounded-2xl border border-slate-200 bg-slate-50 p-6 shadow-sm">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Novo grupo</h3>
                <p class="text-sm text-slate-600">Piloto em Livewire para cadastrar sem recarregar a página.</p>
            </div>

            <button
                type="button"
                class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700"
            >
                Novo grupo
            </button>
        </div>

        @unless($canCreate)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Selecione uma empresa ativa em Empresas para habilitar o cadastro de grupo.
            </div>
        @endunless

        @if($departamentos->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Cadastre um departamento antes de criar grupos.
            </div>
        @endif

        <form wire:submit="save" class="space-y-4">
            <div>
                <label for="lw-grupo-nome" class="block font-semibold text-slate-800">NOME</label>
                <input
                    id="lw-grupo-nome"
                    type="text"
                    wire:model="nome"
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm @error('nome') border-red-500 @enderror"
                    @disabled(! $canCreate)
                    required
                />
                @error('nome')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="lw-grupo-departamento" class="block font-semibold text-slate-800">DEPARTAMENTO</label>
                <select
                    id="lw-grupo-departamento"
                    wire:model="departamentoId"
                    class="w-full rounded border border-slate-300 px-3 py-2 text-sm @error('departamento_id') border-red-500 @enderror"
                    @disabled(! $canCreate)
                    required
                >
                    <option value="">-- Selecione --</option>
                    @foreach($departamentos as $dept)
                        <option value="{{ $dept->id }}">
                            {{ $dept->nome }} ({{ $dept->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $dept->empresa->cnpj_cpf) : 'Sem CNPJ/CPF' }})
                        </option>
                    @endforeach
                </select>
                @error('departamento_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold text-slate-800">CNPJ/CPF DA EMPRESA</label>
                <input
                    type="text"
                    readonly
                    value="{{ optional($departamentos->firstWhere('id', (int) $departamentoId)?->empresa)->cnpj_cpf ? preg_replace('/\D/', '', optional($departamentos->firstWhere('id', (int) $departamentoId)?->empresa)->cnpj_cpf) : ($departamentoId !== '' ? 'Nao vinculado' : 'Selecione um departamento') }}"
                    class="w-full rounded border border-slate-300 bg-slate-100 px-3 py-2 text-sm"
                />
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="rounded bg-indigo-600 px-6 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60"
                    @disabled($departamentos->isEmpty() || ! $canCreate)
                >
                    Salvar
                </button>
                <p class="text-xs text-slate-500">A lista abaixo atualiza automaticamente apos o cadastro.</p>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-300">
        <table class="w-full border-collapse">
            <thead class="border-b border-blue-200 bg-blue-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">NOME</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">DEPARTAMENTO</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">CNPJ/CPF</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">PRODUTOS</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">ACOES</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-300">
                @forelse($grupos as $grupo)
                    <tr class="odd:bg-white even:bg-slate-100 transition-colors duration-150 hover:bg-sky-100">
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $grupo->nome }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $grupo->departamento->nome }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $grupo->empresa?->cnpj_cpf ? preg_replace('/\D/', '', $grupo->empresa->cnpj_cpf) : 'Nao vinculado' }}</td>
                        <td class="px-4 py-3 text-center text-sm text-slate-700">{{ $grupo->produtos_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-center">
                            <a wire:navigate href="{{ route('admin.grupos.edit', ['grupo' => $grupo->id, 'return' => $indexUrl]) }}" class="inline-flex items-center rounded-full border border-blue-600 bg-blue-500 px-2.5 py-1 text-xs font-semibold text-white">Editar</a>
                            <button
                                type="button"
                                wire:click="deleteGroup({{ $grupo->id }})"
                                wire:confirm="Tem certeza?"
                                class="ml-2 inline-flex items-center rounded-full border border-red-600 bg-red-500 px-2.5 py-1 text-xs font-semibold text-white"
                            >
                                Deletar
                            </button>
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

    <div>
        {{ $grupos->links() }}
    </div>
</div>