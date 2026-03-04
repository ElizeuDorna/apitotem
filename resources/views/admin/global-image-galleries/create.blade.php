<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Galeria Imagem Geral · Novo Código
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.global-image-galleries.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código (até 14 dígitos)</label>
                                <input type="text" name="code" inputmode="numeric" maxlength="14" value="{{ old('code') }}" class="w-full border rounded px-3 py-2" required>
                                @error('code')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da imagem/código</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" required>
                                @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        @for ($slot = 1; $slot <= 3; $slot++)
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3">
                                <h3 class="text-sm font-semibold text-gray-800">Imagem {{ $slot }} (nome final: CODIGO_{{ $slot }})</h3>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
                                    <select name="source_type[{{ $slot }}]" class="w-full border rounded px-3 py-2">
                                        <option value="none" @selected(old('source_type.' . $slot, 'none') === 'none')>Sem imagem</option>
                                        <option value="link" @selected(old('source_type.' . $slot) === 'link')>Link externo</option>
                                        <option value="upload" @selected(old('source_type.' . $slot) === 'upload')>Upload</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Link externo</label>
                                    <input type="url" name="external_url[{{ $slot }}]" value="{{ old('external_url.' . $slot) }}" class="w-full border rounded px-3 py-2" placeholder="https://...">
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
