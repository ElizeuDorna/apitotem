<div
    x-data="{
        socialSectionOpen: true,
        templatesSectionOpen: true,
        integrationOpen: {{ (! $instagramConfigured || ! $integrationReady || session()->has('error')) ? 'true' : 'false' }}
    }"
    class="space-y-6 rounded-[2rem] bg-[radial-gradient(circle_at_top_left,_rgba(103,232,249,0.2),_transparent_32%),linear-gradient(180deg,#f0fdfa_0%,#f8fafc_38%,#eef6ff_100%)] px-4 py-5 md:px-6"
>
    <div class="max-w-7xl mx-auto space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        @if ($statusMessage)
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ $statusMessage }}</div>
        @endif

        @if ($errorMessage)
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errorMessage }}</div>
        @endif

        <section class="rounded-3xl border border-cyan-200 bg-white/90 p-5 shadow-sm shadow-cyan-100/60 backdrop-blur-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <button
                    type="button"
                    @click="socialSectionOpen = !socialSectionOpen"
                    class="flex flex-1 items-start justify-between gap-4 rounded-[1.75rem] border border-cyan-200 bg-[linear-gradient(135deg,#ecfeff_0%,#f0f9ff_100%)] px-4 py-4 text-left shadow-sm transition hover:border-cyan-300 hover:bg-[linear-gradient(135deg,#cffafe_0%,#e0f2fe_100%)]"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl font-bold text-slate-900">Rede Social</h2>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $integrationReady ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $integrationReady ? 'Meta configurada para Instagram e Facebook desta empresa' : 'Meta ainda nao configurada para esta empresa' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="hidden text-sm text-slate-500 lg:block">A integracao fica separada da criacao de templates.</p>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-cyan-300 bg-white text-cyan-700 shadow-sm">
                            <span x-show="socialSectionOpen">-</span>
                            <span x-show="!socialSectionOpen">+</span>
                        </span>
                    </div>
                </button>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        @click="integrationOpen = !integrationOpen"
                        x-show="socialSectionOpen"
                        class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        <span x-show="!integrationOpen">Configurar integracao</span>
                        <span x-show="integrationOpen">Fechar configuracao</span>
                    </button>
                </div>
            </div>

            <p x-show="socialSectionOpen" class="mt-1 text-sm text-slate-500">A integracao fica separada da criacao de templates. Depois de configurada, voce escolhe em cada template se quer divulgar no Instagram, no Facebook ou nos dois.</p>

            <div x-cloak x-show="socialSectionOpen && integrationOpen" x-transition class="mt-5 rounded-[2rem] border border-cyan-100 bg-gradient-to-br from-white via-cyan-50/70 to-sky-50 p-5">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="max-w-2xl">
                        <h3 class="text-lg font-bold text-slate-900">Configuracao Meta, Instagram e Facebook</h3>
                        <p class="mt-1 text-sm text-slate-500">Essa configuracao vale para a empresa ativa. Normalmente ela e feita uma vez e depois voce volta aqui so quando precisar reconectar ou trocar a conta.</p>
                    </div>

                    <div class="rounded-2xl px-4 py-2 text-sm font-semibold {{ $integrationReady ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                        @if ($integrationStatus['level'] === 'expired')
                            Token expirado
                        @elseif ($integrationStatus['level'] === 'connected')
                            Conectado
                        @else
                            Desconectado
                        @endif
                    </div>
                </div>

                <div class="mt-5 grid gap-4 xl:grid-cols-[0.64fr_0.36fr]">
                    <div class="space-y-4">
                        <div class="rounded-2xl border px-4 py-4 text-sm {{ $integrationStatus['level'] === 'connected' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : ($integrationStatus['level'] === 'expired' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-slate-200 bg-white text-slate-600') }}">
                            {{ $integrationStatus['message'] }}
                        </div>

                        @if (session('social-media.instagram.pending-selection.accounts'))
                            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-4 text-sm text-indigo-900">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-sm font-semibold">Escolha a conta certa antes de concluir a conexao</div>
                                        <p class="mt-1 text-xs text-indigo-800">A Meta retornou mais de uma pagina com Instagram comercial. Selecione a pagina correta desta empresa para evitar publicar na conta errada.</p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.social-media.instagram.complete-selection') }}" class="mt-4 space-y-3">
                                    @csrf

                                    @foreach (session('social-media.instagram.pending-selection.accounts', []) as $account)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-indigo-200 bg-white px-4 py-3 hover:border-indigo-300">
                                            <input type="radio" name="facebook_page_id" value="{{ $account['facebook_page_id'] }}" class="mt-1 border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('facebook_page_id') === $account['facebook_page_id'] || $loop->first) />
                                            <span>
                                                <span class="block text-sm font-semibold text-slate-900">Facebook: {{ $account['facebook_page_name'] ?: 'Pagina sem nome' }}</span>
                                                <span class="mt-1 block text-xs text-slate-600">Instagram: {{ $account['instagram_username'] ?: 'Conta sem username' }}</span>
                                            </span>
                                        </label>
                                    @endforeach

                                    @error('facebook_page_id')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                                    <button type="submit" class="inline-flex rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Concluir conexao com a pagina selecionada</button>
                                </form>
                            </div>
                        @endif

                        @if (! $instagramConfigured)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                                @if (auth()->user()?->isDefaultAdmin())
                                    Conclua primeiro a configuracao global da plataforma em Config Admin, informando o Meta App ID e a Meta Redirect URI. O Meta App Secret deve permanecer no servidor. Sem isso a conexao real com Instagram e Facebook nao pode ser iniciada.
                                @else
                                    A configuracao global da plataforma Meta ainda nao foi concluida. Assim que ela estiver pronta, a conexao desta empresa podera ser feita aqui normalmente.
                                @endif
                            </div>
                        @else
                            @if ($integrationReady)
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                                    <p><span class="font-semibold">Instagram:</span> {{ $integration->instagram_username ?: 'Conta conectada' }}</p>
                                    <p class="mt-1"><span class="font-semibold">Facebook:</span> {{ $integration->facebook_page_name ?: 'Pagina nao identificada' }}</p>
                                    <p class="mt-1"><span class="font-semibold">Escopo:</span> configuracao por empresa ativa.</p>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button type="button" wire:click="testIntegration" class="rounded-2xl border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                                        Testar conexao Meta
                                    </button>

                                    <form method="POST" action="{{ route('admin.social-media.instagram.disconnect') }}">
                                        @csrf
                                        <button type="submit" class="rounded-2xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Desconectar Meta</button>
                                    </form>
                                </div>
                            @else
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm text-slate-600">
                                    Ao conectar, o sistema identifica a pagina do Facebook vinculada a uma conta comercial do Instagram e habilita a publicacao dos templates desta empresa no Instagram, no Facebook ou nos dois.
                                </div>

                                <a href="{{ route('admin.social-media.instagram.connect') }}" class="inline-flex rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Conectar Meta</a>
                            @endif
                        @endif
                    </div>

                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="rounded-3xl border border-amber-200 bg-white p-6 shadow-sm shadow-amber-100/60">
                <div class="flex items-start justify-between gap-4">
                    <button
                        type="button"
                        @click="templatesSectionOpen = !templatesSectionOpen"
                        class="flex flex-1 items-start justify-between gap-4 rounded-[1.75rem] border border-amber-200 bg-[linear-gradient(135deg,#fff7ed_0%,#fffbeb_100%)] px-4 py-4 text-left shadow-sm transition hover:border-amber-300 hover:bg-[linear-gradient(135deg,#ffedd5_0%,#fef3c7_100%)]"
                    >
                        <div>
                        <h2 class="text-2xl font-bold text-slate-900">Templates de Rede Social</h2>
                        <p class="mt-1 text-sm text-slate-500">Monte um post com titulo, produtos da empresa, imagem principal e agendamento sem mexer no cadastro atual.</p>
                        </div>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-amber-300 bg-white text-amber-700 shadow-sm">
                            <span x-show="templatesSectionOpen">-</span>
                            <span x-show="!templatesSectionOpen">+</span>
                        </span>
                    </button>

                    @if ($editingTemplateId)
                        <button type="button" wire:click="resetForm" x-show="templatesSectionOpen" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Novo template
                        </button>
                    @endif
                </div>

                @if (! $integrationReady)
                    <div x-show="templatesSectionOpen" class="mt-6 rounded-[1.75rem] border border-amber-200 bg-amber-50 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="text-sm text-amber-900">
                                A publicacao depende da integracao Meta mostrada no painel superior.
                            </div>

                            @if ($instagramConfigured)
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('admin.social-media.instagram.connect') }}" class="inline-flex rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Conectar Meta
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <form wire:submit="save" x-show="templatesSectionOpen" class="mt-6 space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-slate-800">Nome interno do template</label>
                            <input type="text" wire:model="nome" class="mt-1 w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ex.: Oferta fim de semana" />
                            @error('nome')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-800">Tipo do template</label>
                            <select wire:model="layoutMode" class="mt-1 w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="product_list">Lista de produtos</option>
                                <option value="image">Imagem com texto</option>
                                <option value="mixed">Imagem + lista de produtos</option>
                            </select>
                            @error('layout_mode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800">Titulo do post</label>
                        <input type="text" wire:model="titulo" class="mt-1 w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ex.: Promoções da semana" />
                        @error('titulo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl border border-violet-200 bg-gradient-to-r from-violet-50 via-fuchsia-50 to-white p-4 shadow-sm">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Modo de publicacao da imagem</h3>
                            <p class="text-xs text-slate-600">Escolha se o post vai sair com uma imagem unica ou como carrossel com as imagens dos produtos selecionados.</p>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-200 bg-white/90 px-4 py-4 shadow-sm transition hover:border-violet-300">
                                <input type="radio" wire:model="imagePublishMode" value="single" class="mt-1 border-slate-300 text-violet-600 focus:ring-violet-500" />
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">Imagem unica</span>
                                    <span class="mt-1 block text-xs text-slate-500">Usa a imagem principal do post como capa unica da publicacao.</span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-fuchsia-200 bg-white/90 px-4 py-4 shadow-sm transition hover:border-fuchsia-300">
                                <input type="radio" wire:model="imagePublishMode" value="product_images" class="mt-1 border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500" />
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">Carrossel com imagens dos produtos</span>
                                    <span class="mt-1 block text-xs text-slate-500">Publica varias imagens usando as fotos dos produtos adicionados neste template.</span>
                                </span>
                            </label>
                        </div>

                        @error('image_publish_mode')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800">Legenda</label>
                        <textarea wire:model="legenda" rows="4" class="mt-1 w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Descreva a campanha, destaque a oferta e complete com hashtags se quiser."></textarea>
                        @error('legenda')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Redes para divulgar</h3>
                            <p class="text-xs text-slate-500">Escolha onde este template sera publicado depois de pronto.</p>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <label class="inline-flex items-start gap-3">
                                    <input type="checkbox" wire:model="publishToInstagram" class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">Instagram</span>
                                        <span class="block text-xs text-slate-500">Publica na conta Instagram da empresa conectada.</span>
                                    </span>
                                </label>

                                <label class="mt-4 inline-flex items-start gap-3">
                                    <input type="checkbox" wire:model="instagramAutoPublish" class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @disabled(! $publishToInstagram) />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">Agendar para Instagram</span>
                                        <span class="block text-xs text-slate-500">Usa a janela de agendamento deste template para publicar automaticamente.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <label class="inline-flex items-start gap-3">
                                    <input type="checkbox" wire:model="publishToFacebook" class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">Facebook</span>
                                        <span class="block text-xs text-slate-500">Publica na pagina Facebook da empresa conectada via Meta.</span>
                                    </span>
                                </label>

                                <label class="mt-4 inline-flex items-start gap-3">
                                    <input type="checkbox" wire:model="facebookAutoPublish" class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @disabled(! $publishToFacebook) />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">Agendar para Facebook</span>
                                        <span class="block text-xs text-slate-500">Usa a janela de agendamento deste template para publicar automaticamente.</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        @error('publish_channels')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl border border-cyan-200 bg-gradient-to-r from-cyan-50 via-sky-50 to-white p-4 shadow-sm">
                        <label class="block text-sm font-semibold text-slate-800">Imagem principal do post</label>
                        <div class="mt-1 flex items-stretch gap-2 md:max-w-2xl">
                            <input id="socialMediaCoverImageInput" type="text" wire:model="coverImageUrl" class="w-full rounded-xl border-cyan-200 bg-white/90 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Se deixar vazio, a primeira imagem do produto sera usada automaticamente" />
                            <a
                                href="{{ route('admin.galeria-imagem.index', ['abrir_form' => 1, 'selecionar_social_media' => 1]) }}"
                                target="_blank"
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-cyan-300 bg-cyan-100 text-cyan-700 shadow-sm hover:bg-cyan-200"
                                title="Buscar imagem na Galeria de Imagem"
                                aria-label="Buscar imagem na Galeria de Imagem"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                    <path fill-rule="evenodd" d="M9 3a6 6 0 1 0 3.873 10.582l3.272 3.273a1 1 0 0 0 1.414-1.414l-3.273-3.272A6 6 0 0 0 9 3Zm-4 6a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                        <p class="mt-2 text-xs text-slate-600">Quando voce adiciona o primeiro produto, a imagem dele ja vira base do template. Se quiser, pode trocar aqui ou por item.</p>
                        @error('cover_image_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Produtos do template</h3>
                                <p class="text-xs text-slate-500">Selecione exatamente os produtos que devem entrar nesta arte social.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                            <div class="rounded-2xl border border-indigo-300 bg-gradient-to-r from-indigo-100 via-sky-100 to-cyan-100 p-3 shadow-md shadow-indigo-100/80">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Pesquisar produto</label>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="productSearch"
                                    class="mt-2 w-full rounded-xl border-indigo-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Digite nome ou codigo do produto"
                                />

                                @if ($productToAdd !== '' && $selectedProductOption)
                                    <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-indigo-200 px-3 py-1 text-xs font-semibold text-indigo-900">
                                        Produto escolhido: {{ $selectedProductOption->NOME }} @if($selectedProductOption->CODIGO) | {{ $selectedProductOption->CODIGO }} @endif
                                    </div>
                                @endif

                                <div class="mt-3 rounded-2xl border border-indigo-200 bg-indigo-50/80 p-2 shadow-inner">
                                    <div class="flex flex-col gap-2 pr-2" style="height: 23rem; overflow-y: scroll; scrollbar-gutter: stable;">
                                        @forelse ($filteredProducts as $product)
                                            <button
                                                type="button"
                                                wire:click="selectProduct({{ $product->id }})"
                                                class="flex w-full shrink-0 items-start justify-between gap-3 rounded-2xl border px-3 py-3 text-left shadow-sm transition {{ (string) $productToAdd === (string) $product->id ? 'border-indigo-500 bg-indigo-200' : 'border-indigo-100 bg-white/95 hover:border-indigo-300 hover:bg-indigo-100/80' }}"
                                                style="min-height: 4.2rem;"
                                            >
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $product->NOME }}</span>
                                                    <span class="mt-1 block text-xs text-slate-500">{{ $product->CODIGO ?: 'Sem codigo' }}</span>
                                                </span>
                                                <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500">Selecionar</span>
                                            </button>
                                        @empty
                                            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-500">
                                                Nenhum produto encontrado com essa busca.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <p class="mt-3 text-xs text-slate-500">A lista exibe 5 produtos por vez. Para ver os demais, use a barra vertical ao lado.</p>
                            </div>

                            <button type="button" wire:click="addProduct" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 lg:mt-7">
                                Adicionar produto
                            </button>
                        </div>

                        @error('selected_products')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                        <div class="mt-4 space-y-3">
                            @forelse ($selectedProducts as $index => $item)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="flex-1 space-y-3">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">{{ $index + 1 }}</span>
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">{{ $item['nome'] }}</p>
                                                    <p class="text-xs text-slate-500">Codigo {{ $item['codigo'] ?: 'sem codigo' }}</p>
                                                </div>
                                            </div>

                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Titulo personalizado</label>
                                                    <input type="text" wire:model="selectedProducts.{{ $index }}.custom_title" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Se vazio, usa o nome do produto" />
                                                    @error('selected_products.'.$index.'.custom_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Trocar imagem deste item</label>
                                                    <input type="text" wire:model="selectedProducts.{{ $index }}.custom_image_url" class="mt-1 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Se vazio, usa a imagem do produto" />
                                                    <p class="mt-1 text-xs text-slate-500">Atual: {{ $item['custom_image_url'] ?: ($item['default_image_url'] ?: 'Sem imagem') }}</p>
                                                    @error('selected_products.'.$index.'.custom_image_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" wire:model="selectedProducts.{{ $index }}.show_price" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                                    <span>Mostrar preco</span>
                                                </label>

                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox" wire:model="selectedProducts.{{ $index }}.show_offer_price" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                                    <span>Mostrar oferta</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="flex gap-2 lg:flex-col">
                                            <button type="button" wire:click="moveProductUp({{ $index }})" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Subir</button>
                                            <button type="button" wire:click="moveProductDown({{ $index }})" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Descer</button>
                                            <button type="button" wire:click="removeProduct({{ $index }})" class="rounded-xl border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Remover</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                                    Nenhum produto adicionado ainda.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-slate-800">Inicio do agendamento</label>
                            <input type="datetime-local" wire:model="scheduledStartAt" class="mt-1 w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p class="mt-1 text-xs text-slate-500">Opcional. Deixe vazio se quiser apenas salvar o template e publicar manualmente quando precisar.</p>
                            @error('scheduled_start_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-800">Fim do agendamento</label>
                            <input type="datetime-local" wire:model="scheduledEndAt" class="mt-1 w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p class="mt-1 text-xs text-slate-500">Opcional. So preencha se quiser limitar ate quando o agendamento automatico pode rodar.</p>
                            @error('scheduled_end_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                        <span class="font-semibold">Publicar agora</span> ignora a janela de agendamento e tenta postar imediatamente usando a integracao Meta da empresa ativa.
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                            {{ $editingTemplateId ? 'Atualizar template' : 'Salvar template' }}
                        </button>

                        @if ($editingTemplateId)
                            <button type="button" wire:click="resetForm" class="rounded-2xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Cancelar edicao
                            </button>
                        @endif
                    </div>
                </form>
            </section>
            <div class="space-y-6">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Pre-visualizacao</h3>
                            <p class="mt-1 text-sm text-slate-500">Visual mais proximo de um post comercial para facilitar a montagem.</p>
                        </div>
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">
                            @if ($publishToInstagram && $publishToFacebook)
                                Instagram + Facebook
                            @elseif ($publishToInstagram)
                                Instagram
                            @elseif ($publishToFacebook)
                                Facebook
                            @else
                                Selecione um canal
                            @endif
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 2xl:grid-cols-[0.72fr_0.28fr]">
                        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-[radial-gradient(circle_at_top,_rgba(244,114,182,0.22),_transparent_34%),linear-gradient(160deg,#fff7ed_0%,#ffffff_46%,#fff1f2_100%)] shadow-inner">
                            <div class="flex items-center justify-between border-b border-white/70 bg-white/70 px-5 py-4 backdrop-blur">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 via-rose-500 to-fuchsia-600 p-[2px] shadow-sm">
                                        <div class="flex h-full w-full items-center justify-center rounded-full bg-white text-[10px] font-black uppercase tracking-[0.16em] text-slate-700">
                                            Post
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">{{ $titulo !== '' ? $titulo : 'Seu template social' }}</div>
                                        <div class="text-xs text-slate-500">{{ $layoutMode === 'image' ? 'Post de imagem' : ($layoutMode === 'mixed' ? 'Post misto' : 'Lista promocional') }}</div>
                                    </div>
                                </div>
                                <div class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-500 shadow-sm ring-1 ring-slate-200">
                                    Feed
                                </div>
                            </div>

                            <div class="space-y-5 p-5">
                                <div class="relative overflow-hidden rounded-[1.75rem] bg-slate-200 shadow-lg shadow-rose-100/60 ring-1 ring-slate-200">
                                    @if ($previewImageUrl !== '')
                                        <img src="{{ $previewImageUrl }}" alt="Imagem principal do template" class="h-96 w-full object-cover" />
                                    @else
                                        <div class="flex h-96 w-full items-center justify-center bg-white px-6 text-center text-base text-slate-400">
                                            A imagem principal aparecera aqui
                                        </div>
                                    @endif

                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/75 via-slate-900/20 to-transparent px-5 pb-5 pt-12">
                                        <div class="max-w-[88%] rounded-3xl bg-white/90 px-5 py-4 shadow-lg backdrop-blur">
                                            <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-rose-500">Campanha</div>
                                            <div class="mt-1 text-2xl font-black leading-tight text-slate-900">{{ $titulo !== '' ? $titulo : 'Titulo do seu post' }}</div>
                                        </div>
                                    </div>
                                </div>

                                @if ($previewProducts !== [])
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach ($previewProducts as $product)
                                            <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-sm ring-1 ring-slate-200">
                                                <div class="flex gap-3 p-3">
                                                    @if ($product['image_url'] !== '')
                                                        <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="h-20 w-20 rounded-[1.2rem] object-cover" />
                                                    @else
                                                        <div class="flex h-20 w-20 items-center justify-center rounded-[1.2rem] bg-slate-100 text-[11px] text-slate-400">Sem imagem</div>
                                                    @endif

                                                    <div class="min-w-0 flex-1 py-1">
                                                        <p class="line-clamp-2 text-sm font-bold leading-5 text-slate-900">{{ $product['name'] }}</p>
                                                        @if ($product['show_offer_price'] && $product['offer'] > 0)
                                                            <p class="mt-2 text-base font-black text-rose-600">R$ {{ number_format($product['offer'], 2, ',', '.') }}</p>
                                                            @if ($product['show_price'] && $product['price'] > 0)
                                                                <p class="text-xs text-slate-400 line-through">R$ {{ number_format($product['price'], 2, ',', '.') }}</p>
                                                            @endif
                                                        @elseif ($product['show_price'] && $product['price'] > 0)
                                                            <p class="mt-2 text-base font-black text-slate-800">R$ {{ number_format($product['price'], 2, ',', '.') }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Legenda final</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">Texto que ira no Instagram</div>
                                    </div>
                                    <div class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-500">{{ count($previewProducts) }} itens</div>
                                </div>

                                <div class="mt-4 rounded-[1.5rem] bg-slate-950 p-4 text-sm leading-6 text-slate-100 shadow-inner">
                                    {!! nl2br(e($previewCaption !== '' ? $previewCaption : 'A legenda montada a partir do titulo, texto e produtos aparecera aqui.')) !!}
                                </div>
                            </div>

                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Templates salvos</h3>
                    <p class="mt-1 text-sm text-slate-500">Voce podera voltar depois para editar, excluir ou publicar manualmente.</p>
                </div>
            </div>

            @if ($integrationStatus['level'] === 'expired')
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                    <span class="font-semibold">Publicacao bloqueada temporariamente.</span>
                    O token da Meta desta empresa expirou. Reconecte a conta antes de usar "Publicar agora" ou aguardar novos agendamentos.
                </div>
            @endif

            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Template</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Redes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Produtos</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Agendamento</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($templates as $template)
                            <tr class="align-top">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $template->nome }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $template->titulo ?: 'Sem titulo definido' }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $template->layout_mode }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">
                                    <div class="flex flex-wrap gap-2">
                                        @if (($template->publish_to_instagram ?? true))
                                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Instagram</span>
                                        @endif
                                        @if (($template->publish_to_facebook ?? false))
                                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Facebook</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $template->templateProducts->count() }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">
                                    <div>Inicio: {{ optional($template->scheduled_start_at)->format('d/m/Y H:i') ?: 'Livre' }}</div>
                                    <div class="mt-1">Fim: {{ optional($template->scheduled_end_at)->format('d/m/Y H:i') ?: 'Sem fim' }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">
                                    <div class="space-y-2">
                                        @if (($template->publish_to_instagram ?? true))
                                            <div>
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $template->instagram_publish_status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($template->instagram_publish_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                                                    Instagram: {{ $template->instagram_publish_status }}
                                                </span>
                                                @if ($template->instagram_publish_id)
                                                    <p class="mt-2 max-w-xs text-xs text-slate-500">ID Instagram: {{ $template->instagram_publish_id }}</p>
                                                @endif
                                                @if ($template->instagram_last_published_at)
                                                    <p class="mt-1 max-w-xs text-xs text-slate-500">Ultima publicacao: {{ $template->instagram_last_published_at->format('d/m/Y H:i') }}</p>
                                                @endif
                                                @if ($template->instagram_last_error)
                                                    <p class="mt-2 max-w-xs text-xs text-red-600">{{ $template->instagram_last_error }}</p>
                                                @endif
                                            </div>
                                        @endif
                                        @if (($template->publish_to_facebook ?? false))
                                            <div>
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $template->facebook_publish_status === 'published' ? 'bg-emerald-100 text-emerald-700' : ($template->facebook_publish_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                                                    Facebook: {{ $template->facebook_publish_status }}
                                                </span>
                                                <p class="mt-2 max-w-xs text-xs text-slate-500">Pagina: {{ $integration->facebook_page_name ?: 'Pagina conectada' }}</p>
                                                @if ($template->facebook_publish_id)
                                                    <p class="mt-1 max-w-xs break-all text-xs text-slate-500">ID Facebook: {{ $template->facebook_publish_id }}</p>
                                                    <a href="https://www.facebook.com/{{ $template->facebook_publish_id }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                                        Abrir publicacao no Facebook
                                                    </a>
                                                @endif
                                                @if ($template->facebook_last_published_at)
                                                    <p class="mt-1 max-w-xs text-xs text-slate-500">Ultima publicacao: {{ $template->facebook_last_published_at->format('d/m/Y H:i') }}</p>
                                                @endif
                                                @if ($template->facebook_last_error)
                                                    <p class="mt-2 max-w-xs text-xs text-red-600">{{ $template->facebook_last_error }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="editTemplate({{ $template->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</button>
                                        <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Tem certeza?" class="rounded-xl border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Excluir</button>
                                        @if ($integrationReady)
                                            <button
                                                type="button"
                                                wire:click="publishNow({{ $template->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="publishNow({{ $template->id }})"
                                                class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70"
                                            >
                                                <span wire:loading.remove wire:target="publishNow({{ $template->id }})">Publicar agora</span>
                                                <span wire:loading wire:target="publishNow({{ $template->id }})">Publicando...</span>
                                            </button>
                                            <span class="inline-flex rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800">Ignora o agendamento</span>
                                            <span wire:loading.inline-flex wire:target="publishNow({{ $template->id }})" class="inline-flex rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                                                Aguardando a Meta publicar...
                                            </span>
                                        @elseif ($integrationStatus['level'] === 'expired')
                                            <span class="inline-flex rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Reconecte a Meta para publicar</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum template social criado ainda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<script>
    const socialMediaCoverImageInput = document.getElementById('socialMediaCoverImageInput');
    const socialMediaCoverImagemSelecionadaStorageKey = 'social_media_cover_image_url_selected';

    function aplicarImagemPrincipalSocialMedia(url) {
        const normalizedUrl = String(url || '').trim();

        if (!socialMediaCoverImageInput || normalizedUrl === '') {
            return;
        }

        socialMediaCoverImageInput.value = normalizedUrl;
        socialMediaCoverImageInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function sincronizarImagemPrincipalSocialMediaDaGaleria() {
        const imagemSelecionada = localStorage.getItem(socialMediaCoverImagemSelecionadaStorageKey);
        if (!imagemSelecionada) {
            return;
        }

        aplicarImagemPrincipalSocialMedia(imagemSelecionada);
        localStorage.removeItem(socialMediaCoverImagemSelecionadaStorageKey);
    }

    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) {
            return;
        }

        const payload = event.data || {};
        if (payload.type !== 'galeriaNovaSelectSocialMediaCoverImage') {
            return;
        }

        aplicarImagemPrincipalSocialMedia(payload.url);
    });

    const imagemPrincipalSocialMediaPendente = localStorage.getItem(socialMediaCoverImagemSelecionadaStorageKey);
    if (imagemPrincipalSocialMediaPendente) {
        aplicarImagemPrincipalSocialMedia(imagemPrincipalSocialMediaPendente);
        localStorage.removeItem(socialMediaCoverImagemSelecionadaStorageKey);
    }

    window.addEventListener('focus', sincronizarImagemPrincipalSocialMediaDaGaleria);
    window.addEventListener('storage', (event) => {
        if (event.key !== socialMediaCoverImagemSelecionadaStorageKey) {
            return;
        }

        sincronizarImagemPrincipalSocialMediaDaGaleria();
    });
</script>