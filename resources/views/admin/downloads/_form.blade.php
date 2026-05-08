@php($isEditing = isset($downloadAsset))

<div class="space-y-5">
    <div>
        <label for="title" class="mb-1 block text-sm font-semibold text-slate-800">Nome para exibição</label>
        <input
            id="title"
            name="title"
            type="text"
            value="{{ old('title', $downloadAsset->title ?? '') }}"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            required
        >
        @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="mb-1 block text-sm font-semibold text-slate-800">Descrição</label>
        <textarea
            id="description"
            name="description"
            rows="4"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >{{ old('description', $downloadAsset->description ?? '') }}</textarea>
        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="file" class="mb-1 block text-sm font-semibold text-slate-800">Arquivo</label>
        <input
            id="file"
            name="file"
            type="file"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            {{ $isEditing ? '' : 'required' }}
        >
        <p class="mt-1 text-xs text-slate-500">
            {{ $isEditing ? 'Envie um novo arquivo apenas se quiser substituir o atual.' : 'Tamanho máximo de 256 MB.' }}
        </p>
        @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    @if ($isEditing)
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <p><strong>Arquivo atual:</strong> {{ $downloadAsset->original_name }}</p>
            <p><strong>Link público:</strong> {{ route('downloads.file', $downloadAsset) }}</p>
        </div>
    @endif
</div>