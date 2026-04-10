<nav x-data="{ open: false }" class="bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
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
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg px-2 py-1 hover:bg-slate-100 transition">
                        <x-application-logo class="block h-9 w-auto fill-current text-slate-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                @php
                    $desktopNavBase = 'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition';
                    $desktopNavActive = 'bg-sky-50 text-sky-700 ring-1 ring-sky-200';
                @endphp
                <div class="hidden sm:ms-8 sm:flex sm:items-center sm:gap-1">
                    <a href="{{ route('dashboard') }}" class="{{ $desktopNavBase }} {{ request()->routeIs('dashboard') ? $desktopNavActive : '' }}">
                        {{ __('Dashboard') }}
                    </a>

                    @if (Auth::user()->hasMenuAccess('editor_template'))
                        <a href="{{ route('admin.templates.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/templates*') ? $desktopNavActive : '' }}">
                            {{ __('Template') }}
                        </a>
                    @endif

                    @if (Auth::user()->hasMenuAccess('cadastro_publico'))
                        <a href="{{ route('register') }}" class="{{ $desktopNavBase }} {{ (request()->routeIs('register') || request()->routeIs('register.users.*')) ? $desktopNavActive : '' }}">
                            {{ __('Cadastro Público') }}
                        </a>
                    @endif

                    <!-- Admin Menu -->
                    @if ($hasCadastroMenu)
                        <div x-data="{ cadastroOpen: {{ $cadastroMenuActive ? 'true' : 'false' }} }" class="relative">
                            <button
                                type="button"
                                @click="cadastroOpen = !cadastroOpen"
                                class="{{ $desktopNavBase }} {{ $cadastroMenuActive ? $desktopNavActive : '' }}"
                            >
                                <span>{{ __('Cadastro') }}</span>
                                <svg class="ml-2 h-4 w-4 transition-transform" :class="{ 'rotate-180': cadastroOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.512a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="cadastroOpen"
                                @click.outside="cadastroOpen = false"
                                class="absolute left-0 z-50 mt-2 w-56 overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg"
                            >
                                @if ($navUser->hasMenuAccess('produtos'))
                                    <a href="/admin/produtos" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 {{ request()->is('admin/produtos*') ? 'bg-sky-50 text-sky-700' : '' }}">
                                        {{ __('Produtos') }}
                                    </a>
                                @endif
                                @if ($navUser->hasMenuAccess('departamentos'))
                                    <a href="/admin/departamentos" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 {{ request()->is('admin/departamentos*') ? 'bg-sky-50 text-sky-700' : '' }}">
                                        {{ __('Departamentos') }}
                                    </a>
                                @endif
                                @if ($navUser->hasMenuAccess('grupos'))
                                    <a href="/admin/grupos" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 {{ request()->is('admin/grupos*') ? 'bg-sky-50 text-sky-700' : '' }}">
                                        {{ __('Grupos') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if (Auth::user()->hasMenuAccess('empresas'))
                        <a href="/admin/empresas" class="{{ $desktopNavBase }} {{ request()->is('admin/empresas*') ? $desktopNavActive : '' }}">
                            {{ __('Empresas') }}
                        </a>
                    @endif
                    <a href="{{ route('admin.financeiro.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/financeiro*') ? $desktopNavActive : '' }}">
                        {{ __('Financeiro') }}
                    </a>
                    @if (Auth::user()->hasMenuAccess('configuracao'))
                        <a href="/admin/configuracao" class="{{ $desktopNavBase }} {{ request()->is('admin/configuracao') ? $desktopNavActive : '' }}">
                            {{ __('Configuração') }}
                        </a>
                        <a href="{{ route('admin.web-screen-config.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/configuracao-tela-web') ? $desktopNavActive : '' }}">
                            {{ __('Configuracao da Tela') }}
                        </a>
                        <a href="{{ route('admin.organizar-lista.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/organizar-lista') ? $desktopNavActive : '' }}">
                            {{ __('Organizar Lista') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('galeria_nova'))
                        <a href="{{ route('admin.galeria-imagem.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/galeria-imagem*') ? $desktopNavActive : '' }}">
                            {{ __('Galeria de Imagem') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('token_api'))
                        <a href="{{ route('admin.api-token.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/api-token*') ? $desktopNavActive : '' }}">
                            {{ __('Token API') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('gestao_tvs'))
                        <a href="{{ route('admin.devices.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/devices*') ? $desktopNavActive : '' }}">
                            {{ __('Gestão de TVs') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('ativar_tv'))
                        <a href="{{ route('admin.activate-tv.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/ativar-tv*') ? $desktopNavActive : '' }}">
                            {{ __('Ativar TV') }}
                        </a>
                    @endif
                    @if (Auth::user()->isDefaultAdmin())
                        <a href="{{ route('admin.user-permissions.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/permissoes-usuarios*') ? $desktopNavActive : '' }}">
                            {{ __('Permissões de Acesso') }}
                        </a>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <div class="flex items-center gap-2">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 text-sm leading-4 font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>

                    @if ($isRevendaNav)
                        <a href="{{ route('admin.empresas.index') }}" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 transition ease-in-out duration-150">
                            TrocarEmp
                        </a>
                    @else
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition ease-in-out duration-150">
                                Deslogar
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:bg-slate-100 focus:text-slate-700 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    @if ($isRevendaNav && $empresaAtivaNav)
        <div class="border-t border-emerald-100 bg-emerald-50/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
                <p class="text-sm text-emerald-900">
                    Empresa ativa: <strong>{{ $empresaAtivaNav->nome }}</strong>.
                    Todas as alterações feitas em qualquer menu serão aplicadas somente nesta empresa até você trocar a seleção.
                </p>
            </div>
        </div>
    @endif

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-200 bg-white/95 backdrop-blur">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (Auth::user()->hasMenuAccess('editor_template'))
                <x-responsive-nav-link :href="route('admin.templates.index')" :active="request()->is('admin/templates*')">
                    {{ __('Template') }}
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->hasMenuAccess('cadastro_publico'))
                <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register') || request()->routeIs('register.users.*')">
                    {{ __('Cadastro Público') }}
                </x-responsive-nav-link>
            @endif

            <!-- Admin Menu (Mobile) -->
            @if ($hasCadastroMenu)
                <div x-data="{ cadastroMobileOpen: {{ $cadastroMenuActive ? 'true' : 'false' }} }" class="border-b border-slate-100 pb-1">
                    <button
                        type="button"
                        @click="cadastroMobileOpen = !cadastroMobileOpen"
                        class="flex w-full items-center justify-between px-3 py-2 text-left text-base font-medium {{ $cadastroMenuActive ? 'border-l-4 border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-800' }}"
                    >
                        <span>{{ __('Cadastro') }}</span>
                        <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': cadastroMobileOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.512a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-cloak x-show="cadastroMobileOpen" class="space-y-1 pb-2">
                        @if ($navUser->hasMenuAccess('produtos'))
                            <x-responsive-nav-link href="/admin/produtos" :active="request()->is('admin/produtos*')">
                                {{ __('Produtos') }}
                            </x-responsive-nav-link>
                        @endif
                        @if ($navUser->hasMenuAccess('departamentos'))
                            <x-responsive-nav-link href="/admin/departamentos" :active="request()->is('admin/departamentos*')">
                                {{ __('Departamentos') }}
                            </x-responsive-nav-link>
                        @endif
                        @if ($navUser->hasMenuAccess('grupos'))
                            <x-responsive-nav-link href="/admin/grupos" :active="request()->is('admin/grupos*')">
                                {{ __('Grupos') }}
                            </x-responsive-nav-link>
                        @endif
                    </div>
                </div>
            @endif
            @if (Auth::user()->hasMenuAccess('empresas'))
                <x-responsive-nav-link href="/admin/empresas">
                    {{ __('Empresas') }}
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('admin.financeiro.index')">
                {{ __('Financeiro') }}
            </x-responsive-nav-link>
            @if (Auth::user()->hasMenuAccess('configuracao'))
                <x-responsive-nav-link href="/admin/configuracao">
                    {{ __('Configuração') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.web-screen-config.edit')">
                    {{ __('Configuracao da Tela') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.organizar-lista.edit')">
                    {{ __('Organizar Lista') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('galeria_nova'))
                <x-responsive-nav-link :href="route('admin.galeria-imagem.index')">
                    {{ __('Galeria de Imagem') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('token_api'))
                <x-responsive-nav-link :href="route('admin.api-token.index')">
                    {{ __('Token API') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('gestao_tvs'))
                <x-responsive-nav-link :href="route('admin.devices.index')">
                    {{ __('Gestão de TVs') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('ativar_tv'))
                <x-responsive-nav-link :href="route('admin.activate-tv.index')">
                    {{ __('Ativar TV') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->isDefaultAdmin())
                <x-responsive-nav-link :href="route('admin.user-permissions.index')">
                    {{ __('Permissões de Acesso') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
