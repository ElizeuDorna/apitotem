<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Galeria Imagem Geral · Editar Código
        </h2>
    </x-slot>

    @php
        $itemsBySlot = $gallery->items->keyBy('slot');
    @endphp

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.global-image-galleries.update', $gallery) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código (até 14 dígitos)</label>
                                <input type="text" name="code" inputmode="numeric" maxlength="14" value="{{ old('code', $gallery->code) }}" class="w-full border rounded px-3 py-2" required>
                                @error('code')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da imagem/código</label>
                                <input type="text" name="name" value="{{ old('name', $gallery->name) }}" class="w-full border rounded px-3 py-2" required>
                                @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        @for ($slot = 1; $slot <= 3; $slot++)
                            @php
                                $item = $itemsBySlot->get($slot);
                                $defaultType = $item?->source_type ?? 'none';
                                $imageUrl = null;

                                if ($item?->source_type === 'link') {
                                    $imageUrl = $item->external_url;
                                } elseif ($item?->source_type === 'upload' && $item->file_path) {
                                    $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($item->file_path);
                                }
                            @endphp

                            <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3">
                                <h3 class="text-sm font-semibold text-gray-800">Imagem {{ $slot }} ({{ old('code', $gallery->code) }}_{{ $slot }})</h3>

                                @if ($imageUrl)
                                    <div>
                                        <img src="{{ $imageUrl }}" alt="Imagem {{ $slot }}" class="h-24 w-24 object-cover rounded border">
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
                                    <select name="source_type[{{ $slot }}]" class="w-full border rounded px-3 py-2">
                                        <option value="none" @selected(old('source_type.' . $slot, $defaultType) === 'none')>Sem imagem</option>
                                        <option value="link" @selected(old('source_type.' . $slot, $defaultType) === 'link')>Link externo</option>
                                        <option value="upload" @selected(old('source_type.' . $slot, $defaultType) === 'upload')>Upload</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Link externo</label>
                                    <input type="url" name="external_url[{{ $slot }}]" value="{{ old('external_url.' . $slot, $item?->source_type === 'link' ? $item?->external_url : '') }}" class="w-full border rounded px-3 py-2" placeholder="https://...">
                                    @error('external_url.' . $slot)<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload da imagem</label>
                                    <input type="file" name="upload_image[{{ $slot }}]" accept="image/*" class="w-full border rounded px-3 py-2 bg-white">
                                    @error('upload_image.' . $slot)<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endfor

                        <div class="flex items-center gap-3">
                            <x-primary-button>Salvar</x-primary-button>
                            <a href="{{ route('admin.global-image-galleries.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
