<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar TV
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.devices.update', $device) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input name="nome" type="text" value="{{ old('nome', $device->nome) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <x-input-error :messages="$errors->get('nome')" class="mt-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Local</label>
                            <input name="local" type="text" value="{{ old('local', $device->local) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <x-input-error :messages="$errors->get('local')" class="mt-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Template</label>
                            <select name="template_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Sem template</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" @selected((string) old('template_id', $configuration->template_id) === (string) $template->id)>
                                        {{ $template->nome }} ({{ $template->tipo_layout }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('template_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Atualizar produtos (seg)</label>
                                <input name="atualizar_produtos_segundos" type="number" min="5" max="3600" value="{{ old('atualizar_produtos_segundos', $configuration->atualizar_produtos_segundos) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Volume</label>
                                <input name="volume" type="number" min="0" max="100" value="{{ old('volume', $configuration->volume) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Orientação</label>
                                <select name="orientacao" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="landscape" @selected(old('orientacao', $configuration->orientacao) === 'landscape')>Landscape</option>
                                    <option value="portrait" @selected(old('orientacao', $configuration->orientacao) === 'portrait')>Portrait</option>
                                </select>
                            </div>
                        </div>

                        <label class="inline-flex items-center">
                            <input type="checkbox" name="ativo" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" @checked(old('ativo', $device->ativo))>
                            <span class="ml-2 text-sm text-gray-700">TV ativa</span>
                        </label>

                        <div class="flex justify-between">
                            <a href="{{ route('admin.devices.index') }}" class="text-sm text-gray-600 underline">Voltar</a>
                            <x-primary-button>Salvar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
