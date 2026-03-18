<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo Template</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @php
                        $layoutLabels = [
                            'grade' => ['pt' => 'Grade', 'en' => 'Grid'],
                            'lista' => ['pt' => 'Lista', 'en' => 'List'],
                            'video_background' => ['pt' => 'Video de fundo', 'en' => 'Video background'],
                            'promocao' => ['pt' => 'Promocao', 'en' => 'Promotion'],
                            'misto' => ['pt' => 'Misto', 'en' => 'Mixed'],
                        ];
                    @endphp

                    @if ($adminSemEmpresaAtiva ?? false)
                        <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-800 mb-4">
                            Selecione uma empresa ativa para criar template.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.templates.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input name="nome" type="text" value="{{ old('nome') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de layout</label>
                            <select id="tipoLayoutSelect" name="tipo_layout" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @foreach($layouts as $layout)
                                    <option
                                        value="{{ $layout }}"
                                        data-layout-key="{{ $layout }}"
                                        data-label-pt="{{ $layoutLabels[$layout]['pt'] ?? $layout }}"
                                        data-label-en="{{ $layoutLabels[$layout]['en'] ?? $layout }}"
                                    >{{ $layoutLabels[$layout]['pt'] ?? $layout }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($isDefaultAdmin)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Empresa</label>
                                <select name="empresa_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="">Selecione</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" @selected((string) old('empresa_id') === (string) $empresa->id)>
                                            {{ $empresa->NOME }} - {{ $empresa->CNPJ_CPF }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('empresa_id')" class="mt-2" />
                            </div>
                        @endif

                        @if($devices->isNotEmpty())
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Aplicar em dispositivo (opcional)</label>
                                <select name="device_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Nao aplicar agora</option>
                                    @foreach($devices as $device)
                                        <option value="{{ $device->id }}" @selected((string) old('device_id') === (string) $device->id)>
                                            {{ $device->nome }}{{ $device->local ? ' - ' . $device->local : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('device_id')" class="mt-2" />
                            </div>
                        @endif

                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="capture_web_config" value="0">
                            <input type="checkbox" name="capture_web_config" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('capture_web_config', '1') === '1')>
                            <span class="text-sm text-gray-700">Capturar configuracoes atuais da Totem Web</span>
                        </label>

                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="is_default_web" value="0">
                            <input type="checkbox" name="is_default_web" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_default_web') === '1')>
                            <span class="text-sm text-gray-700">Definir como template padrão da empresa</span>
                        </label>
                        <div class="flex justify-between">
                            <a href="{{ route('admin.templates.index') }}" class="text-sm text-gray-600 underline">Voltar</a>
                            <x-primary-button>Criar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const select = document.getElementById('tipoLayoutSelect');
            if (!select) {
                return;
            }

            const isEnglishBrowser = String(navigator.language || '').toLowerCase().startsWith('en');

            Array.from(select.options).forEach((option) => {
                const labelPt = String(option.getAttribute('data-label-pt') || option.value);
                const labelEn = String(option.getAttribute('data-label-en') || option.value);
                option.textContent = isEnglishBrowser ? labelEn : labelPt;
            });
        })();
    </script>
</x-app-layout>
