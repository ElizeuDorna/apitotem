<div
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
        },
        updateDocument(event) {
            this.documentValue = this.formatDocument(event.target.value);
        }
    }"
    class="max-w-2xl mx-auto bg-white p-8 shadow"
>
    <h2 class="mb-6 text-2xl font-bold">Editar Produto</h2>

    @if ($statusMessage)
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-green-800">
            {{ $statusMessage }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block font-semibold">CÓDIGO (14 caracteres)</label>
            <input type="text" maxlength="14" wire:model="codigo" class="w-full rounded border px-2 py-1 @error('CODIGO') border-red-500 @enderror" />
            @error('CODIGO')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">NOME</label>
            <input type="text" wire:model="nome" class="w-full rounded border px-2 py-1 @error('NOME') border-red-500 @enderror" required />
            @error('NOME')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">CNPJ/CPF</label>
            <input type="text" x-bind:value="documentValue" x-on:input="updateDocument($event)" class="w-full rounded border px-2 py-1 @error('cnpj_cpf') border-red-500 @enderror" maxlength="18" inputmode="numeric" required />
            @error('cnpj_cpf')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold">PREÇO</label>
                <input type="number" step="0.01" wire:model="preco" class="w-full rounded border px-2 py-1 @error('PRECO') border-red-500 @enderror" required />
                @error('PRECO')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-semibold">OFERTA (opcional)</label>
                <input type="number" step="0.01" wire:model="oferta" class="w-full rounded border px-2 py-1 @error('OFERTA') border-red-500 @enderror" />
                @error('OFERTA')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block font-semibold">IMAGEM (URL)</label>
            <div class="mt-1 flex items-stretch gap-2 md:max-w-xl">
                <input type="text" x-model="imageValue" class="w-full rounded border px-2 py-1 @error('IMG') border-red-500 @enderror" placeholder="https://... ou /storage/..." />
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
            <select wire:model.live="departamentoId" class="w-full rounded border px-2 py-1 @error('departamento_id') border-red-500 @enderror" required>
                <option value="">-- Selecione --</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->nome }}</option>
                @endforeach
            </select>
            @error('departamento_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-semibold">GRUPO</label>
            <select wire:model="grupoId" class="w-full rounded border px-2 py-1 @error('grupo_id') border-red-500 @enderror" required>
                <option value="">{{ $departamentoId === '' ? '-- Selecione departamento primeiro --' : '-- Selecione --' }}</option>
                @foreach ($availableGroups as $group)
                    <option value="{{ $group->id }}">{{ $group->nome }}</option>
                @endforeach
            </select>
            @error('grupo_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex space-x-2 pt-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded bg-indigo-600 px-6 py-2 text-white disabled:cursor-not-allowed disabled:opacity-60">Atualizar</button>
            <a href="{{ $returnUrl }}" class="rounded bg-gray-400 px-6 py-2 text-white">Cancelar</a>
        </div>
    </form>
</div>