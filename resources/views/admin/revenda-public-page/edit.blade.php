<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Frente Publica da Revenda</h2>
                @if ($empresa)
                    <p class="mt-1 text-sm text-gray-500">Revenda ativa: {{ $empresa->nome }}</p>
                @endif
            </div>
            @if ($empresa && ($empresa->public_page_enabled ?? false) && $empresa->public_page_slug)
                <a href="{{ route('revenda.site.home', ['slug' => $empresa->public_page_slug]) }}" target="_blank" class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                    Abrir Link Publico
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            @if ($setupError)
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                    <div class="font-semibold">Edicao indisponivel</div>
                    <div class="mt-1">{{ $setupError }}</div>
                </div>
            @endif

            @if ($empresa)
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Configuracao da revenda</h3>
                        <p class="mt-1 text-sm text-slate-500">O admin padrao libera a personalizacao no cadastro da revenda. Quando ativo, o link publico usa o slug proprio da revenda.</p>
                    </div>
                    <div class="px-6 py-5 grid gap-3 text-sm">
                        <div><strong>Permissao ativa:</strong> {{ ($empresa->public_page_enabled ?? false) ? 'Sim' : 'Nao' }}</div>
                        <div><strong>Slug publico:</strong> {{ $empresa->public_page_slug ?: 'Nao definido' }}</div>
                        @if ($empresa->public_page_slug)
                            <div><strong>Link:</strong> <a class="text-sky-700 hover:text-sky-900" href="{{ route('revenda.site.home', ['slug' => $empresa->public_page_slug]) }}" target="_blank">{{ route('revenda.site.home', ['slug' => $empresa->public_page_slug]) }}</a></div>
                        @endif
                    </div>
                </section>
            @endif

            @if ($empresa && ! $accessBlocked && $page)
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Conteudo da frente publica</h3>
                            <p class="mt-1 text-sm text-slate-500">Altere os textos que aparecem na home, no sobre e no contato da sua revenda.</p>
                        </div>
                        <a href="{{ route('admin.revenda-public-page-slides.index') }}" class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                            Gerenciar Slides
                        </a>
                    </div>
                    <div class="p-6 text-gray-900">
                        <form method="POST" action="{{ route('admin.revenda-public-page.update') }}" class="space-y-6">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Titulo principal da home</label>
                                <input type="text" name="hero_title" value="{{ old('hero_title', $page->hero_title) }}" class="w-full border rounded px-3 py-2">
                                @error('hero_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Subtitulo principal</label>
                                <textarea name="hero_subtitle" rows="3" class="w-full border rounded px-3 py-2">{{ old('hero_subtitle', $page->hero_subtitle) }}</textarea>
                                @error('hero_subtitle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Titulo do Sobre</label>
                                    <input type="text" name="about_title" value="{{ old('about_title', $page->about_title) }}" class="w-full border rounded px-3 py-2">
                                    @error('about_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Titulo do Contato</label>
                                    <input type="text" name="contact_title" value="{{ old('contact_title', $page->contact_title) }}" class="w-full border rounded px-3 py-2">
                                    @error('contact_title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Conteudo do Sobre</label>
                                    <textarea name="about_content" rows="8" class="w-full border rounded px-3 py-2">{{ old('about_content', $page->about_content) }}</textarea>
                                    @error('about_content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Conteudo do Contato</label>
                                    <textarea name="contact_content" rows="8" class="w-full border rounded px-3 py-2">{{ old('contact_content', $page->contact_content) }}</textarea>
                                    @error('contact_content')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email de contato</label>
                                    <input type="email" name="contact_email" value="{{ old('contact_email', $page->contact_email) }}" class="w-full border rounded px-3 py-2">
                                    @error('contact_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Telefone</label>
                                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $page->contact_phone) }}" class="w-full border rounded px-3 py-2">
                                    @error('contact_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">WhatsApp</label>
                                    <input type="text" name="contact_whatsapp" value="{{ old('contact_whatsapp', $page->contact_whatsapp) }}" class="w-full border rounded px-3 py-2">
                                    @error('contact_whatsapp')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Texto do botao principal</label>
                                    <input type="text" name="cta_label" value="{{ old('cta_label', $page->cta_label) }}" class="w-full border rounded px-3 py-2">
                                    @error('cta_label')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Link do botao principal</label>
                                    <input type="url" name="cta_link" value="{{ old('cta_link', $page->cta_link) }}" class="w-full border rounded px-3 py-2" placeholder="https://...">
                                    @error('cta_link')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <x-primary-button>Salvar Frente Publica</x-primary-button>
                                <a href="{{ route('admin.revenda-public-page-slides.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Ir para slides</a>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>