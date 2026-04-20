<nav x-data="{ open: false }">
    @php
        $navUser = Auth::user();
        $isRevendaNav = $navUser ? \App\Support\EmpresaContext::requiresSelection($navUser) : false;
        $empresaAtivaNav = $navUser ? \App\Support\EmpresaContext::activeEmpresa($navUser) : null;
        $hasCadastroMenu = $navUser && (
            $navUser->hasMenuAccess('produtos')
            || $navUser->hasMenuAccess('departamentos')
            || $navUser->hasMenuAccess('grupos')
        );
        $cadastroMenuActive = request()->is('admin/produtos*')
            || request()->is('admin/departamentos*')
            || request()->is('admin/grupos*');
        $desktopNavBase = 'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900';
        $desktopNavActive = 'bg-sky-50 text-sky-700 ring-1 ring-sky-200';
        $mobileNavBase = 'block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-100';
        $mobileNavActive = 'bg-sky-50 text-sky-700';
    @endphp

    <aside class="hidden sm:fixed sm:inset-y-0 sm:left-0 sm:z-40 sm:flex sm:w-72 sm:flex-col sm:border-r sm:border-slate-200 sm:bg-white sm:shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl transition hover:bg-slate-50">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-lg">
                    <x-application-logo class="block h-7 w-auto fill-current text-white" />
                </div>
                <div>
                    <div class="text-base font-black tracking-[0.18em] text-slate-900 uppercase">Totem</div>
                    <div class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Painel administrativo</div>
                </div>
            </a>
        </div>

        @if ($isRevendaNav && $empresaAtivaNav)
            <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Empresa ativa</p>
                <p class="mt-2 text-sm font-semibold text-emerald-950">{{ $empresaAtivaNav->nome }}</p>
                <p class="mt-1 text-xs leading-5 text-emerald-800">As alteracoes do painel serao aplicadas somente nesta empresa ate voce trocar a selecao.</p>
            </div>
        @endif

        <div class="flex-1 overflow-y-auto px-4 py-5">
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" class="{{ $desktopNavBase }} {{ request()->routeIs('dashboard') ? $desktopNavActive : '' }}">
                    <span>{{ __('Dashboard') }}</span>
                </a>

                @if ($navUser->hasMenuAccess('editor_template'))
                    <a href="{{ route('admin.templates.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/templates*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Template') }}</span>
                    </a>
                @endif

                @if ($navUser->hasMenuAccess('cadastro_publico'))
                    <a href="{{ route('register') }}" class="{{ $desktopNavBase }} {{ (request()->routeIs('register') || request()->routeIs('register.users.*')) ? $desktopNavActive : '' }}">
                        <span>{{ __('Cadastro Usuarios') }}</span>
                    </a>
                @endif

                @if ($hasCadastroMenu)
                    <div x-data="{ cadastroOpen: {{ $cadastroMenuActive ? 'true' : 'false' }} }" class="rounded-2xl border border-slate-200/80 bg-slate-50/70 px-2 py-2">
                        <button
                            type="button"
                            @click="cadastroOpen = !cadastroOpen"
                            class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-700 transition hover:bg-white"
                        >
                            <span>{{ __('Cadastro') }}</span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': cadastroOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.512a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-cloak x-show="cadastroOpen" class="mt-2 space-y-1 border-t border-slate-200 pt-2">
                            @if ($navUser->hasMenuAccess('produtos'))
                                <a href="/admin/produtos" class="{{ $desktopNavBase }} {{ request()->is('admin/produtos*') ? $desktopNavActive : '' }}">
                                    <span>{{ __('Produtos') }}</span>
                                </a>
                            @endif
                            @if ($navUser->hasMenuAccess('departamentos'))
                                <a href="/admin/departamentos" class="{{ $desktopNavBase }} {{ request()->is('admin/departamentos*') ? $desktopNavActive : '' }}">
                                    <span>{{ __('Departamentos') }}</span>
                                </a>
                            @endif
                            @if ($navUser->hasMenuAccess('grupos'))
                                <a href="/admin/grupos" class="{{ $desktopNavBase }} {{ request()->is('admin/grupos*') ? $desktopNavActive : '' }}">
                                    <span>{{ __('Grupos') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($navUser->hasMenuAccess('empresas'))
                    <a href="/admin/empresas" class="{{ $desktopNavBase }} {{ request()->is('admin/empresas*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Empresas') }}</span>
                    </a>
                @endif

                <a href="{{ route('admin.financeiro.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/financeiro*') ? $desktopNavActive : '' }}">
                    <span>{{ __('Financeiro') }}</span>
                </a>

                @if ($navUser->hasMenuAccess('configuracao'))
                    <a href="/admin/configuracao" class="{{ $desktopNavBase }} {{ request()->is('admin/configuracao') ? $desktopNavActive : '' }}">
                        <span>{{ __('Config Android') }}</span>
                    </a>
                    <a href="{{ route('admin.web-screen-config.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/configuracao-tela-web') ? $desktopNavActive : '' }}">
                        <span>{{ __('Config Totem Web') }}</span>
                    </a>
                    <a href="{{ route('admin.organizar-lista.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/organizar-lista') ? $desktopNavActive : '' }}">
                        <span>{{ __('Organizar Lista') }}</span>
                    </a>
                @endif

                @if ($navUser->hasMenuAccess('galeria_nova'))
                    <a href="{{ route('admin.galeria-imagem.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/galeria-imagem*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Galeria de Imagem') }}</span>
                    </a>
                @endif

                @if ($navUser->hasMenuAccess('token_api'))
                    <a href="{{ route('admin.api-token.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/api-token*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Token API') }}</span>
                    </a>
                @endif

                @if ($navUser->hasMenuAccess('gestao_tvs'))
                    <a href="{{ route('admin.devices.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/devices*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Gestão de TVs') }}</span>
                    </a>
                @endif

                @if ($navUser->hasMenuAccess('ativar_tv'))
                    <a href="{{ route('admin.activate-tv.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/ativar-tv*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Ativar TV') }}</span>
                    </a>
                @endif

                @if ($navUser->isDefaultAdmin())
                    <a href="{{ route('admin.home-carousel.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/home-carousel*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Carrossel Inicial') }}</span>
                    </a>
                    <a href="{{ route('admin.revenda-public-page.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/revenda/frente-publica*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Frente Publica Revenda') }}</span>
                    </a>
                    <a href="{{ route('admin.user-permissions.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/permissoes-usuarios*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Permissões de Acesso') }}</span>
                    </a>
                @elseif (($navUser->empresa?->isRevenda() ?? false) && ($navUser->empresa?->public_page_enabled ?? false))
                    <a href="{{ route('admin.revenda-public-page.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/revenda/frente-publica*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Frente Publica Revenda') }}</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">{{ $navUser->name }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $navUser->email }}</div>

                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        {{ __('Profile') }}
                    </a>

                    @if ($isRevendaNav)
                        <a href="{{ route('admin.empresas.index') }}" class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">
                            TrocarEmp
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                            Deslogar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    <div class="border-b border-slate-200 bg-white shadow-sm sm:hidden">
        <div class="flex items-center justify-between px-4 py-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-lg">
                    <x-application-logo class="block h-6 w-auto fill-current text-white" />
                </div>
                <div>
                    <div class="text-sm font-black uppercase tracking-[0.18em] text-slate-900">Totem</div>
                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-500">Painel</div>
                </div>
            </a>

            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-b border-slate-200 bg-white sm:hidden">
        @if ($isRevendaNav && $empresaAtivaNav)
            <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-3">
                <p class="text-sm text-emerald-900">
                    Empresa ativa: <strong>{{ $empresaAtivaNav->nome }}</strong>
                </p>
            </div>
        @endif

        <div class="space-y-1 px-3 py-3">
            <a href="{{ route('dashboard') }}" class="{{ $mobileNavBase }} {{ request()->routeIs('dashboard') ? $mobileNavActive : '' }}">{{ __('Dashboard') }}</a>

            @if ($navUser->hasMenuAccess('editor_template'))
                <a href="{{ route('admin.templates.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/templates*') ? $mobileNavActive : '' }}">{{ __('Template') }}</a>
            @endif

            @if ($navUser->hasMenuAccess('cadastro_publico'))
                <a href="{{ route('register') }}" class="{{ $mobileNavBase }} {{ (request()->routeIs('register') || request()->routeIs('register.users.*')) ? $mobileNavActive : '' }}">{{ __('Cadastro Usuarios') }}</a>
            @endif

            @if ($hasCadastroMenu)
                <div x-data="{ cadastroMobileOpen: {{ $cadastroMenuActive ? 'true' : 'false' }} }" class="rounded-xl border border-slate-200 bg-slate-50 px-2 py-2">
                    <button
                        type="button"
                        @click="cadastroMobileOpen = !cadastroMobileOpen"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-700"
                    >
                        <span>{{ __('Cadastro') }}</span>
                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': cadastroMobileOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.512a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-cloak x-show="cadastroMobileOpen" class="mt-2 space-y-1 border-t border-slate-200 pt-2">
                        @if ($navUser->hasMenuAccess('produtos'))
                            <a href="/admin/produtos" class="{{ $mobileNavBase }} {{ request()->is('admin/produtos*') ? $mobileNavActive : '' }}">{{ __('Produtos') }}</a>
                        @endif
                        @if ($navUser->hasMenuAccess('departamentos'))
                            <a href="/admin/departamentos" class="{{ $mobileNavBase }} {{ request()->is('admin/departamentos*') ? $mobileNavActive : '' }}">{{ __('Departamentos') }}</a>
                        @endif
                        @if ($navUser->hasMenuAccess('grupos'))
                            <a href="/admin/grupos" class="{{ $mobileNavBase }} {{ request()->is('admin/grupos*') ? $mobileNavActive : '' }}">{{ __('Grupos') }}</a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($navUser->hasMenuAccess('empresas'))
                <a href="/admin/empresas" class="{{ $mobileNavBase }} {{ request()->is('admin/empresas*') ? $mobileNavActive : '' }}">{{ __('Empresas') }}</a>
            @endif
            <a href="{{ route('admin.financeiro.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/financeiro*') ? $mobileNavActive : '' }}">{{ __('Financeiro') }}</a>
            @if ($navUser->hasMenuAccess('configuracao'))
                <a href="/admin/configuracao" class="{{ $mobileNavBase }} {{ request()->is('admin/configuracao') ? $mobileNavActive : '' }}">{{ __('Config Android') }}</a>
                <a href="{{ route('admin.web-screen-config.edit') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/configuracao-tela-web') ? $mobileNavActive : '' }}">{{ __('Config Totem Web') }}</a>
                <a href="{{ route('admin.organizar-lista.edit') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/organizar-lista') ? $mobileNavActive : '' }}">{{ __('Organizar Lista') }}</a>
            @endif
            @if ($navUser->hasMenuAccess('galeria_nova'))
                <a href="{{ route('admin.galeria-imagem.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/galeria-imagem*') ? $mobileNavActive : '' }}">{{ __('Galeria de Imagem') }}</a>
            @endif
            @if ($navUser->hasMenuAccess('token_api'))
                <a href="{{ route('admin.api-token.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/api-token*') ? $mobileNavActive : '' }}">{{ __('Token API') }}</a>
            @endif
            @if ($navUser->hasMenuAccess('gestao_tvs'))
                <a href="{{ route('admin.devices.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/devices*') ? $mobileNavActive : '' }}">{{ __('Gestão de TVs') }}</a>
            @endif
            @if ($navUser->hasMenuAccess('ativar_tv'))
                <a href="{{ route('admin.activate-tv.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/ativar-tv*') ? $mobileNavActive : '' }}">{{ __('Ativar TV') }}</a>
            @endif
            @if ($navUser->isDefaultAdmin())
                <a href="{{ route('admin.home-carousel.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/home-carousel*') ? $mobileNavActive : '' }}">{{ __('Carrossel Inicial') }}</a>
                <a href="{{ route('admin.revenda-public-page.edit') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/revenda/frente-publica*') ? $mobileNavActive : '' }}">{{ __('Frente Publica Revenda') }}</a>
                <a href="{{ route('admin.user-permissions.index') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/permissoes-usuarios*') ? $mobileNavActive : '' }}">{{ __('Permissões de Acesso') }}</a>
            @elseif (($navUser->empresa?->isRevenda() ?? false) && ($navUser->empresa?->public_page_enabled ?? false))
                <a href="{{ route('admin.revenda-public-page.edit') }}" class="{{ $mobileNavBase }} {{ request()->is('admin/revenda/frente-publica*') ? $mobileNavActive : '' }}">{{ __('Frente Publica Revenda') }}</a>
            @endif
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <div class="text-sm font-semibold text-slate-900">{{ $navUser->name }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ $navUser->email }}</div>
            <div class="mt-4 space-y-2">
                <a href="{{ route('profile.edit') }}" class="{{ $mobileNavBase }}">{{ __('Profile') }}</a>
                @if ($isRevendaNav)
                    <a href="{{ route('admin.empresas.index') }}" class="{{ $mobileNavBase }}">TrocarEmp</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-left text-base font-medium text-rose-700">Deslogar</button>
                </form>
            </div>
        </div>
    </div>
</nav>
