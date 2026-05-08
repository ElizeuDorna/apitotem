<div class="space-y-6" x-data="{ formEnabled: false }">
    <div class="max-w-6xl mx-auto rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-bold">Produtos</h2>
            <button
                type="button"
                x-on:click="formEnabled = true; document.getElementById('product-create-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                @disabled(! $canCreate)
                class="rounded bg-green-600 px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60"
            >
                + Novo Produto
            </button>
        </div>

        @if ($statusMessage)
            <div class="mb-4 rounded border border-green-200 bg-green-100 p-3 text-green-800">{{ $statusMessage }}</div>
        @endif

        @error('delete')
            <div class="mb-4 rounded border border-red-200 bg-red-100 p-3 text-red-800">{{ $message }}</div>
        @enderror

        @unless($canCreate)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Selecione uma empresa ativa em Empresas para habilitar o cadastro de produto.
            </div>
        @endunless

        <div
            id="product-create-form"
            x-data="{
                imageValue: $wire.entangle('img', true),
                documentValue: $wire.entangle('cnpjCpf', true),
                storageKey: 'produto_imagem_url_selecionada',
                previewLoaded: false,
                messageHandler: null,
                focusHandler: null,
                storageHandler: null,
                init() {
                    this.imageValue = this.normalizeImageUrl(this.imageValue);
                    this.documentValue = this.formatDocument(String(this.documentValue || ''));
                    this.previewLoaded = this.hasImage();
                    this.messageHandler = (event) => {
                        if (event.origin !== window.location.origin) return;
                        const payload = event.data || {};
                        if (payload.type !== 'galeriaNovaSelectImage') return;
                        this.applySelectedImage(payload.url);
                    };
                    this.focusHandler = () => this.syncSelectedImageFromStorage();
                    this.storageHandler = (event) => {
                        if (event.key !== this.storageKey) return;
                        this.syncSelectedImageFromStorage();
                    };
                    window.addEventListener('message', this.messageHandler);
                    window.addEventListener('focus', this.focusHandler);
                    window.addEventListener('storage', this.storageHandler);
                    this.syncSelectedImageFromStorage();
                },
                destroy() {
                    window.removeEventListener('message', this.messageHandler);
                    window.removeEventListener('focus', this.focusHandler);
                    window.removeEventListener('storage', this.storageHandler);
                },
                hasImage() {
                    return String(this.imageValue || '').trim() !== '';
                },
                applySelectedImage(url) {
                    const normalized = this.normalizeImageUrl(url);
                    if (normalized === '') return;
                    this.imageValue = normalized;
                    this.previewLoaded = false;
                },
                syncSelectedImageFromStorage() {
                    const selected = localStorage.getItem(this.storageKey);
                    if (!selected) return;
                    this.applySelectedImage(selected);
                    localStorage.removeItem(this.storageKey);
                },
                clearImage() {
                    this.imageValue = '';
                    this.previewLoaded = false;
                },
                normalizeImageUrl(value) {
                    const normalized = String(value || '').trim();
                    if (normalized === '') return '';

                    const legacyPrefixes = [
                        '/storage/galeria-nova/',
                        '/storage/galeria-geral/',
                        '/storage/empresas/',
                        '/storage/home-carousel/',
                    ];

                    for (const prefix of legacyPrefixes) {
                        if (normalized.startsWith(prefix)) {
                            return '/storage-images/' + normalized.slice('/storage/'.length);
                        }
                    }

                    return normalized;
                },
                formatDocument(value) {
                    const digits = String(value || '').replace(/\D/g, '').slice(0, 14);
                    if (digits.length <= 11) {
                        return digits
                            .replace(/(\d{3})(\d)/, '$1.$2')
                            .replace(/(\d{3})(\d)/, '$1.$2')
                            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    }
                    return digits
                        .replace(/^(\d{2})(\d)/, '$1.$2')
                        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                        .replace(/\.(\d{3})(\d)/, '.$1/$2')
                        .replace(/(\d{4})(\d)/, '$1-$2');
                }
            }"
            class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-6"
        >
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Novo produto sem recarregar a página</h3>

            <form wire:submit="save" class="space-y-4">
                <div x-show="!formEnabled" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Clique em <span class="font-semibold">Novo Produto</span> para habilitar o cadastro.
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block font-semibold">CÓDIGO (14 caracteres)</label>
                        <input type="text" maxlength="14" wire:model="codigo" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="w-full rounded border px-2 py-1 @error('CODIGO') border-red-500 @enderror" />
                        @error('CODIGO')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block font-semibold">NOME</label>
                        <input type="text" wire:model="nome" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="w-full rounded border px-2 py-1 @error('NOME') border-red-500 @enderror" required />
                        @error('NOME')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">CNPJ/CPF</label>
                    <input type="text" x-model="documentValue" readonly class="w-full rounded border bg-gray-100 px-2 py-1" />
                    @error('cnpj_cpf')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold">PREÇO</label>
                        <input type="number" step="0.01" wire:model="preco" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="w-full rounded border px-2 py-1 @error('PRECO') border-red-500 @enderror" required />
                        @error('PRECO')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block font-semibold">OFERTA (opcional)</label>
                        <input type="number" step="0.01" wire:model="oferta" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="w-full rounded border px-2 py-1 @error('OFERTA') border-red-500 @enderror" />
                        @error('OFERTA')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">IMAGEM (URL)</label>
                    <div class="mt-1 flex items-stretch gap-2 md:max-w-xl">
                        <input type="text" x-model="imageValue" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="w-full rounded border px-2 py-1 @error('IMG') border-red-500 @enderror" placeholder="https://... ou /storage/..." />
                        <a href="{{ $galleryPickerUrl }}" target="_blank" class="inline-flex items-center rounded border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Buscar imagem</a>
                    </div>
                    @error('IMG')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-2" x-show="hasImage()" x-cloak>
                        <div class="inline-flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                            <img :src="imageValue" alt="Preview" x-on:load="previewLoaded = true" x-on:error="previewLoaded = false" x-show="previewLoaded" class="rounded border border-gray-200 object-cover" style="height:{{ $previewSize }}px!important;width:{{ $previewSize }}px!important;min-width:{{ $previewSize }}px!important;max-width:{{ $previewSize }}px!important;max-height:{{ $previewSize }}px!important;" />
                            <button type="button" x-on:click="clearImage()" class="text-xs font-medium text-red-600 hover:text-red-800">Limpar</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold">DEPARTAMENTO</label>
                    <select wire:model.live="departamentoId" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="w-full rounded border px-2 py-1 @error('departamento_id') border-red-500 @enderror" required>
                        <option value="">-- Selecione --</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->nome }}</option>
                        @endforeach
                    </select>
                    @error('departamento_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-semibold">GRUPO</label>
                    <select wire:model="grupoId" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="w-full rounded border px-2 py-1 @error('grupo_id') border-red-500 @enderror" required>
                        <option value="">{{ $departamentoId === '' ? '-- Selecione departamento primeiro --' : '-- Selecione --' }}</option>
                        @foreach ($availableGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->nome }}</option>
                        @endforeach
                    </select>
                    @error('grupo_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" x-bind:disabled="!formEnabled || {{ $canCreate ? 'false' : 'true' }}" class="rounded bg-indigo-600 px-6 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60">Salvar</button>
                    <p class="text-xs text-slate-500">A lista abaixo atualiza automaticamente após o envio.</p>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-300">
            <table class="w-full border-collapse">
                <thead class="border-b border-blue-200 bg-blue-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">CODIGO</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">NOME</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">CNPJ/CPF</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">PRECO</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">OFERTA</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">DEPARTAMENTO</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-800">GRUPO</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-800">ACOES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300">
                    @forelse($products as $product)
                        <tr class="odd:bg-white even:bg-slate-100 transition-colors duration-150 hover:bg-sky-100">
                            <td class="px-4 py-3 font-mono text-sm text-slate-700">{{ $product->CODIGO }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $product->NOME }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $product->cnpj_cpf }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($product->PRECO, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">R$ {{ number_format($product->OFERTA ?? 0, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $product->departamento?->nome ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $product->grupo?->nome ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <a wire:navigate href="{{ route('admin.produtos.edit', ['produto' => $product->id, 'return' => $indexUrl]) }}" class="inline-flex items-center rounded-full border border-blue-600 bg-blue-500 px-2.5 py-1 text-xs font-semibold text-white">Editar</a>
                                <button type="button" wire:click="deleteProduct({{ $product->id }})" wire:confirm="Tem certeza?" class="ml-2 inline-flex items-center rounded-full border border-red-600 bg-red-500 px-2.5 py-1 text-xs font-semibold text-white">Deletar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Nenhum produto cadastrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</div>