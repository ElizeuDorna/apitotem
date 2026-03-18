<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Galeria de Imagem
            </h2>
            <button
                type="button"
                id="toggleGaleriaNovaFormBtn"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            >
                Nova imagem
            </button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="galeriaNovaFormWrapper" class="{{ request()->boolean('abrir_form') ? '' : 'hidden' }} rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Adicionar imagem</h3>

                <form method="POST" action="{{ route('admin.galeria-imagem.store') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Link externo (opcional)</label>
                            <input type="url" name="external_url" value="{{ old('external_url') }}" class="w-full border rounded px-3 py-2" placeholder="https://...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload (opcional)</label>
                            <input type="file" name="upload_image" accept="image/*" class="w-full border rounded px-3 py-2 bg-white">
                        </div>
                    </div>

                    <p class="text-xs text-gray-500">Preencha link externo ou selecione uma imagem para upload.</p>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Salvar imagem
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Listagem de imagens</h3>

                @forelse ($galleries as $gallery)
                    @if ($loop->first)
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @endif

                    @php
                        $resolvedUrl = \App\Http\Controllers\Admin\GaleriaNovaController::itemUrl($gallery);
                    @endphp

                    <div class="rounded-md border border-gray-200 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900 line-clamp-2">
                                {{ $gallery->name }}
                            </p>
                            <form method="POST" action="{{ route('admin.galeria-imagem.destroy', $gallery) }}" onsubmit="return confirm('Deseja excluir este item da Galeria de Imagem?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">Excluir</button>
                            </form>
                        </div>

                        @if ($resolvedUrl)
                            <div class="mt-2 rounded border border-gray-200 bg-gray-50 p-2">
                                <img src="{{ $resolvedUrl }}" alt="Imagem {{ $gallery->name }}" class="h-28 w-full rounded object-cover border">
                                <p class="mt-2 text-xs text-gray-600 break-all">{{ $gallery->source_type === 'link' ? 'Link externo' : 'Upload' }}</p>

                                @if (request()->boolean('selecionar_produto'))
                                    <button
                                        type="button"
                                        class="mt-2 w-full rounded bg-indigo-600 px-2 py-1 text-xs font-semibold text-white hover:bg-indigo-500"
                                        data-select-produto-image-url="{{ $resolvedUrl }}"
                                    >
                                        Selecionar para produto
                                    </button>
                                @endif

                                @if (request()->boolean('selecionar_slide'))
                                    <button
                                        type="button"
                                        class="mt-2 w-full rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-500"
                                        data-select-slide-image-url="{{ $resolvedUrl }}"
                                    >
                                        Selecionar para slide lateral
                                    </button>
                                @endif
                            </div>
                        @else
                            <p class="mt-2 text-sm text-gray-500">Sem imagem valida.</p>
                        @endif
                    </div>

                    @if ($loop->last)
                        </div>
                    @endif
                @empty
                    <p class="text-sm text-gray-500">Nenhuma imagem cadastrada na Galeria de Imagem.</p>
                @endforelse

                <div class="mt-4">
                    {{ $galleries->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        const toggleGaleriaNovaFormBtn = document.getElementById('toggleGaleriaNovaFormBtn');
        const galeriaNovaFormWrapper = document.getElementById('galeriaNovaFormWrapper');
        const produtoImagemSelecionadaStorageKey = 'produto_imagem_url_selecionada';
        const slideLateralImagemSelecionadaStorageKey = 'right_sidebar_slide_image_url_selected';

        if (toggleGaleriaNovaFormBtn && galeriaNovaFormWrapper) {
            const startsOpen = !galeriaNovaFormWrapper.classList.contains('hidden');
            toggleGaleriaNovaFormBtn.textContent = startsOpen ? 'Fechar formulario' : 'Nova imagem';

            toggleGaleriaNovaFormBtn.addEventListener('click', () => {
                galeriaNovaFormWrapper.classList.toggle('hidden');
                const formIsVisible = !galeriaNovaFormWrapper.classList.contains('hidden');
                toggleGaleriaNovaFormBtn.textContent = formIsVisible ? 'Fechar formulario' : 'Nova imagem';
            });

            @if ($errors->any())
                galeriaNovaFormWrapper.classList.remove('hidden');
                toggleGaleriaNovaFormBtn.textContent = 'Fechar formulario';
            @endif
        }

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const selectProdutoButton = target.closest('[data-select-produto-image-url]');
            const selectSlideButton = target.closest('[data-select-slide-image-url]');
            const selectButton = selectProdutoButton || selectSlideButton;
            if (!(selectButton instanceof HTMLElement)) {
                return;
            }

            const isProdutoSelection = selectButton.hasAttribute('data-select-produto-image-url');
            const isSlideSelection = selectButton.hasAttribute('data-select-slide-image-url');
            const selectedUrl = String(
                selectButton.getAttribute('data-select-produto-image-url')
                    || selectButton.getAttribute('data-select-slide-image-url')
                    || ''
            ).trim();
            if (selectedUrl === '') {
                return;
            }

            if (isProdutoSelection) {
                localStorage.setItem(produtoImagemSelecionadaStorageKey, selectedUrl);
            }

            if (isSlideSelection) {
                localStorage.setItem(slideLateralImagemSelecionadaStorageKey, selectedUrl);
            }

            if (window.opener && window.opener !== window) {
                if (isProdutoSelection) {
                    window.opener.postMessage({
                        type: 'galeriaNovaSelectImage',
                        url: selectedUrl,
                    }, window.location.origin);
                }

                if (isSlideSelection) {
                    window.opener.postMessage({
                        type: 'galeriaNovaSelectSlideImage',
                        url: selectedUrl,
                    }, window.location.origin);
                }
            }

            window.close();
        });
    </script>
</x-app-layout>
