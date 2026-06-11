<div class="space-y-6">
    @if (($statusMessage ?? null) || session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ $statusMessage ?? session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Planos do self-service</h2>
            <p class="mt-1 text-sm text-slate-600">Gerencie planos públicos, ciclo de cobrança e o trial padrão de 7 dias.</p>
        </div>

        <button type="button" wire:click="startCreate" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500">
            + Novo plano
        </button>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        As cobranças recorrentes continuam seguindo a quantidade de TVs ativas. A cobrança inicial do auto cadastro sai com 1 unidade e vencimento no final do trial.
    </div>

    @if ($formEnabled)
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h3 class="text-xl font-bold text-slate-900">{{ $editingPlanId ? 'Editar plano' : 'Criar plano' }}</h3>
                <button type="button" wire:click="cancel" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
            </div>

            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block font-semibold">Código</label>
                        <input type="text" wire:model.blur="code" class="w-full rounded border px-3 py-2 @error('code') border-red-500 @enderror" placeholder="trimestral" required>
                        <p class="mt-1 text-xs text-slate-500">Use letras minúsculas, números e hífen.</p>
                        @error('code')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block font-semibold">Nome</label>
                        <input type="text" wire:model.blur="name" class="w-full rounded border px-3 py-2 @error('name') border-red-500 @enderror" required>
                        @error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">Descrição</label>
                    <textarea wire:model.blur="description" rows="2" class="w-full rounded border px-3 py-2 @error('description') border-red-500 @enderror" placeholder="Ex: cobrança trimestral automática."></textarea>
                    @error('description')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="block font-semibold">Ciclo</label>
                        <select wire:model.live="intervaloCobrancaDias" class="w-full rounded border px-3 py-2 @error('intervaloCobrancaDias') border-red-500 @enderror">
                            @foreach ($intervalOptions as $days => $label)
                                <option value="{{ $days }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('intervaloCobrancaDias')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block font-semibold">Valor unitário</label>
                        <input type="number" step="0.01" min="0.01" wire:model.blur="valorUnitario" class="w-full rounded border px-3 py-2 @error('valorUnitario') border-red-500 @enderror" required>
                        @error('valorUnitario')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block font-semibold">Trial em dias</label>
                        <input type="number" min="0" max="365" wire:model.blur="trialDays" class="w-full rounded border px-3 py-2 @error('trialDays') border-red-500 @enderror" required>
                        @error('trialDays')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block font-semibold">Ordem</label>
                        <input type="number" min="0" wire:model.blur="sortOrder" class="w-full rounded border px-3 py-2 @error('sortOrder') border-red-500 @enderror" required>
                        @error('sortOrder')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-800">
                        <input type="checkbox" wire:model.live="isSelfService" class="rounded border-slate-300">
                        Disponível no auto cadastro público
                    </label>
                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-800">
                        <input type="checkbox" wire:model.live="isActive" class="rounded border-slate-300">
                        Plano ativo
                    </label>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="rounded bg-indigo-600 px-6 py-2 text-white" wire:loading.attr="disabled">Salvar</button>
                    <button type="button" wire:click="cancel" class="rounded bg-gray-400 px-6 py-2 text-white">Cancelar</button>
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-slate-300 bg-white">
        <table class="w-full border-collapse">
            <thead class="border-b border-blue-200 bg-blue-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">Plano</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">Ciclo</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-800">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">Trial</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-300">
                @forelse ($plans as $plan)
                    <tr class="odd:bg-white even:bg-slate-100">
                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $plan->code }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            <div class="font-medium text-slate-900">{{ $plan->name }}</div>
                            <div class="text-xs text-slate-500">{{ $plan->description ?: 'Sem descrição' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $intervalOptions[$plan->intervalo_cobranca_dias] ?? ($plan->intervalo_cobranca_dias . ' dias') }}</td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">R$ {{ number_format((float) $plan->valor_unitario, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center text-sm text-slate-700">{{ $plan->trial_days }} dias</td>
                        <td class="px-4 py-3 text-center text-sm text-slate-700">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $plan->is_active ? 'border-emerald-200 bg-emerald-100 text-emerald-800' : 'border-red-200 bg-red-100 text-red-800' }}">
                                {{ $plan->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                            @if ($plan->is_self_service)
                                <div class="mt-1 text-xs text-sky-700">Disponível no self-service</div>
                            @else
                                <div class="mt-1 text-xs text-slate-500">Somente uso interno</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" wire:click="editPlan({{ $plan->id }})" class="inline-flex items-center rounded-full border border-blue-600 bg-blue-500 px-3 py-1 text-xs font-semibold text-white">Editar</button>
                                <button type="button" wire:click="toggleActive({{ $plan->id }})" class="inline-flex items-center rounded-full border border-slate-400 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $plan->is_active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Nenhum plano cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>