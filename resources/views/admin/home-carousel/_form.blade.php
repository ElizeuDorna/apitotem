@php
    $editing = isset($slide);
    $resolvedPreview = old('image_source_type', $slide->image_source_type ?? 'upload') === 'link'
        ? old('image_url', $slide->image_url ?? '')
        : ($editing ? $slide->resolvedImageUrl() : null);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
            <input type="text" name="title" value="{{ old('title', $slide->title ?? '') }}" class="w-full border rounded px-3 py-2">
            @error('title')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtítulo</label>
            <textarea name="subtitle" rows="4" class="w-full border rounded px-3 py-2">{{ old('subtitle', $slide->subtitle ?? '') }}</textarea>
            @error('subtitle')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Texto do botão</label>
                <input type="text" name="button_label" value="{{ old('button_label', $slide->button_label ?? '') }}" class="w-full border rounded px-3 py-2" placeholder="Ex.: Saiba mais">
                @error('button_label')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Link do botão</label>
                <input type="url" name="button_link" value="{{ old('button_link', $slide->button_link ?? '') }}" class="w-full border rounded px-3 py-2" placeholder="https://...">
                @error('button_link')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Origem da imagem</label>
                <select name="image_source_type" class="w-full border rounded px-3 py-2" data-carousel-source>
                    <option value="upload" @selected(old('image_source_type', $slide->image_source_type ?? 'upload') === 'upload')>Upload</option>
                    <option value="link" @selected(old('image_source_type', $slide->image_source_type ?? 'upload') === 'link')>Link da internet</option>
                </select>
                @error('image_source_type')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ordem</label>
                <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $slide->sort_order ?? 0) }}" class="w-full border rounded px-3 py-2" required>
                @error('sort_order')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div data-upload-field>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload da imagem</label>
            <input type="file" name="image_file" accept="image/*" class="w-full border rounded px-3 py-2 bg-white">
            @error('image_file')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div data-link-field>
            <label class="block text-sm font-medium text-gray-700 mb-1">Link direto da imagem</label>
            <input type="url" name="image_url" value="{{ old('image_url', $slide->image_url ?? '') }}" class="w-full border rounded px-3 py-2" placeholder="https://...">
            @error('image_url')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" @checked(old('is_active', $slide->is_active ?? true))>
            Slide ativo
        </label>
    </div>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <p class="text-sm font-semibold text-gray-800 mb-3">Pré-visualização atual</p>
        <div class="overflow-hidden rounded-xl bg-gray-900 min-h-[320px] relative">
            @if ($resolvedPreview)
                <img src="{{ $resolvedPreview }}" alt="Prévia do slide" class="w-full h-80 object-cover">
            @else
                <div class="h-80 flex items-center justify-center text-sm text-gray-300">Nenhuma imagem definida ainda.</div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/30 to-transparent"></div>
            <div class="absolute left-0 right-0 bottom-0 p-6 text-white">
                <div class="text-2xl font-bold">{{ old('title', $slide->title ?? 'Título do slide') }}</div>
                <div class="mt-2 text-sm text-white/80">{{ old('subtitle', $slide->subtitle ?? 'Subtítulo opcional para destacar uma informação importante na tela inicial.') }}</div>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500">Use upload ou link. Se escolher upload na edição e não enviar novo arquivo, a imagem atual será mantida.</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sourceField = document.querySelector('[data-carousel-source]');
        const uploadField = document.querySelector('[data-upload-field]');
        const linkField = document.querySelector('[data-link-field]');

        if (!sourceField || !uploadField || !linkField) {
            return;
        }

        const syncFields = () => {
            const isUpload = sourceField.value === 'upload';
            uploadField.style.display = isUpload ? 'block' : 'none';
            linkField.style.display = isUpload ? 'none' : 'block';
        };

        sourceField.addEventListener('change', syncFields);
        syncFields();
    });
</script>