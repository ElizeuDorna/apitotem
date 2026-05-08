<div class="space-y-6" x-data="companyManagementPanel()">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($statusMessage)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ $statusMessage }}</div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-slate-900">Empresas</h2>
        <button type="button" wire:click="startCreate" x-on:click="formEnabled = true" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-white shadow-sm transition hover:bg-green-700">+ Nova Empresa</button>
    </div>

    <div x-show="formEnabled || @js($formEnabled)" x-cloak class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between gap-3">
            <h3 class="text-xl font-bold text-slate-900">Cadastrar empresa</h3>
            <button type="button" wire:click="cancelCreate" x-on:click="formEnabled = false" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
        </div>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block font-semibold">CODIGO</label>
                    <input type="text" value="Gerado automaticamente" class="w-full rounded border bg-gray-100 px-2 py-1 text-gray-600" disabled />
                </div>
                <div>
                    <label class="block font-semibold">CNPJ/CPF</label>
                    <input type="text" wire:model.blur="cnpjCpf" x-on:input="$el.value = formatCpfCnpj($el.value); $wire.cnpjCpf = $el.value" class="w-full rounded border px-2 py-1 @error('cnpj_cpf') border-red-500 @enderror" maxlength="18" inputmode="numeric" required />
                    @error('cnpj_cpf')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block font-semibold">NOME</label>
                <input type="text" wire:model.blur="nome" class="w-full rounded border px-2 py-1 @error('nome') border-red-500 @enderror" required />
                @error('nome')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block font-semibold">RAZAO SOCIAL</label>
                <input type="text" wire:model.blur="razaosocial" class="w-full rounded border px-2 py-1 @error('razaosocial') border-red-500 @enderror" required />
                @error('razaosocial')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block font-semibold">EMAIL</label>
                    <input type="email" wire:model.blur="email" class="w-full rounded border px-2 py-1 @error('email') border-red-500 @enderror" required />
                    @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block font-semibold">FONE</label>
                    <input type="text" wire:model.blur="fone" x-on:input="$el.value = formatPhone($el.value); $wire.fone = $el.value" class="w-full rounded border px-2 py-1 @error('fone') border-red-500 @enderror" maxlength="15" inputmode="numeric" required />
                    @error('fone')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block font-semibold">SENHA INTEGRACAO API (OPCIONAL)</label>
                <input type="password" wire:model.blur="senhaIntegracaoApi" class="w-full rounded border px-2 py-1 @error('senha_integracao_api') border-red-500 @enderror" autocomplete="new-password" />
                <p class="mt-1 text-sm text-gray-600">Preencha somente se esta empresa for usar integracao com a API.</p>
                @error('senha_integracao_api')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                O token da API continua sendo gerado automaticamente para a empresa.
            </div>

            @if($isDefaultAdmin)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block font-semibold">NIVEL DE ACESSO</label>
                        <select wire:model.live="nivelAcesso" class="w-full rounded border px-2 py-1 @error('nivel_acesso') border-red-500 @enderror">
                            <option value="1">Cliente Final (Nivel 1)</option>
                            <option value="2">Revenda (Nivel 2)</option>
                        </select>
                        @error('nivel_acesso')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="$wire.nivelAcesso !== '2'">
                        <label class="block font-semibold">VINCULAR A REVENDA (OPCIONAL)</label>
                        <select wire:model.live="revendaId" class="w-full rounded border px-2 py-1 @error('revenda_id') border-red-500 @enderror">
                            <option value="">Sem vinculo de revenda</option>
                            @foreach($revendas as $revenda)
                                <option value="{{ $revenda->id }}">{{ $revenda->nome ?: ($revenda->fantasia ?: ('Revenda #' . $revenda->id)) }}</option>
                            @endforeach
                        </select>
                        @error('revenda_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div x-show="$wire.nivelAcesso === '2'" class="space-y-4 rounded border border-sky-200 bg-sky-50 px-4 py-4">
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-sky-900">
                            <input type="checkbox" wire:model.live="publicPageEnabled" class="rounded border-sky-300">
                            Permitir que esta revenda personalize a propria frente publica
                        </label>
                        @error('public_page_enabled')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block font-semibold">SLUG PUBLICO DA REVENDA</label>
                        <input type="text" wire:model.blur="publicPageSlug" class="w-full rounded border px-2 py-1 @error('public_page_slug') border-red-500 @enderror" placeholder="minha-revenda">
                        <p class="mt-1 text-xs text-sky-900">O link publico ficara em /r/slug-da-revenda</p>
                        @error('public_page_slug')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block font-semibold">ENDERECO</label>
                    <input type="text" wire:model.blur="endereco" class="w-full rounded border px-2 py-1 @error('endereco') border-red-500 @enderror" />
                    @error('endereco')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block font-semibold">BAIRRO</label>
                    <input type="text" wire:model.blur="bairro" class="w-full rounded border px-2 py-1 @error('bairro') border-red-500 @enderror" />
                    @error('bairro')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block font-semibold">NUMERO</label>
                    <input type="text" wire:model.blur="numero" class="w-full rounded border px-2 py-1 @error('numero') border-red-500 @enderror" />
                    @error('numero')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block font-semibold">CEP</label>
                    <input type="text" wire:model.blur="cep" class="w-full rounded border px-2 py-1 @error('cep') border-red-500 @enderror" />
                    @error('cep')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-2 pt-4">
                <button type="submit" class="rounded bg-indigo-600 px-6 py-2 text-white" wire:loading.attr="disabled">Salvar</button>
                <button type="button" wire:click="cancelCreate" x-on:click="formEnabled = false" class="rounded bg-gray-400 px-6 py-2 text-white">Cancelar</button>
            </div>
        </form>
    </div>

    @if($podePesquisar)
        <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4">
            <p class="text-sm font-semibold text-blue-900">Pesquisar empresa</p>
            <p class="mt-1 text-xs text-blue-700">Digite nome ou CNPJ para filtrar a grade.</p>

            @if((int) ($empresaAtivaId ?? 0) > 0)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                    <span>Empresa ativa: <strong>{{ $empresaAtivaNome ?? ('ID #' . $empresaAtivaId) }}</strong></span>
                    <form method="POST" action="{{ route('admin.empresas.limpar-selecao', ['search' => $search]) }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">Limpar selecao</button>
                    </form>
                </div>
            @endif

            <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-end">
                <div class="w-full md:flex-1">
                    <label for="empresa-search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-blue-900">Nome ou CNPJ</label>
                    <input id="empresa-search" type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-md border border-blue-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none" placeholder="Ex: Mercado Alfa ou 12.345.678/0001-90">
                </div>
                @if($search !== '')
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$set('search', '')" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpar</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-slate-300 bg-white">
        <table class="w-full border-collapse">
            <thead class="border-b border-blue-200 bg-blue-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">CODIGO</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">NOME</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">NIVEL</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">REVENDA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">RAZAO SOCIAL</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">CNPJ/CPF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">EMAIL</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">FONE</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">ACOES</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-300">
                @forelse($empresas as $empresa)
                    @php
                        $isAdminOuRevenda = auth()->user()->isDefaultAdmin() || ($empresaVinculada && $empresaVinculada->isRevenda());
                        $revendaId = $empresaVinculada?->id;
                        $podeSelecionarLinha = $isAdminOuRevenda && (auth()->user()->isDefaultAdmin() || (int) ($empresa->revenda_id ?? 0) === (int) $revendaId);
                        $isEmpresaAtivaLinha = (int) ($empresaAtivaId ?? 0) === (int) $empresa->id;
                    @endphp
                    <tr class="odd:bg-white even:bg-slate-100 {{ $isEmpresaAtivaLinha ? 'bg-emerald-100/80 hover:bg-emerald-200/70' : 'hover:bg-sky-100' }} {{ $podeSelecionarLinha ? 'cursor-pointer' : '' }}"
                        @if($podeSelecionarLinha)
                            onclick="if (!event.target.closest('[data-actions]')) { window.location.href='{{ route('admin.empresas.selecionar.get', ['empresa' => $empresa->id, 'search' => $search]) }}'; }"
                        @endif>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->codigo }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $empresa->nome }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ (int) ($empresa->nivel_acesso ?? 1) === 2 ? 'Revenda (N2)' : 'Cliente Final (N1)' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->revenda?->nome ?: ($empresa->revenda?->fantasia ?: '-') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->razaosocial }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->cnpj_cpf }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->email }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $empresa->fone }}</td>
                        <td class="px-4 py-3 text-center" data-actions="true">
                            <a href="{{ route('admin.empresas.edit', $empresa->id) }}" wire:navigate class="inline-flex items-center rounded-full border border-blue-600 bg-blue-500 px-2.5 py-1 text-xs font-semibold text-white">Editar</a>
                            <button type="button" wire:click="deleteCompany({{ $empresa->id }})" wire:confirm="Tem certeza?" class="ml-2 inline-flex items-center rounded-full border border-red-600 bg-red-500 px-2.5 py-1 text-xs font-semibold text-white">Deletar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                            @if($search !== '')
                                Nenhuma empresa encontrada para a pesquisa.
                            @else
                                Nenhuma empresa cadastrada.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $empresas->links() }}
    </div>
</div>

@script
<script>
    Alpine.data('companyManagementPanel', () => ({
        formEnabled: @js($formEnabled),
        formatCpfCnpj(value) {
            const digits = value.replace(/\D/g, '').slice(0, 14);
            if (digits.length <= 11) {
                return digits.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            return digits.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
        },
        formatPhone(value) {
            const digits = value.replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 10) {
                return digits.replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*$/, (_, d1, d2, d3) => {
                    let result = '';
                    if (d1) result += `(${d1}`;
                    if (d1.length === 2) result += ') ';
                    if (d2) result += d2;
                    if (d3) result += `-${d3}`;
                    return result;
                });
            }
            return digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*$/, '($1) $2-$3');
        },
    }));
</script>
@endscript