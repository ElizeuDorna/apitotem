<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php
        $navUser = Auth::user();
        $isRevendaNav = $navUser ? \App\Support\EmpresaContext::requiresSelection($navUser) : false;
        $empresaAtivaNav = $navUser ? \App\Support\EmpresaContext::activeEmpresa($navUser) : null;
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (Auth::user()->hasMenuAccess('cadastro_publico'))
                        <x-nav-link :href="route('register')" :active="request()->routeIs('register') || request()->routeIs('register.users.*')">
                            {{ __('Cadastro Público') }}
                        </x-nav-link>
                    @endif

                    <!-- Admin Menu -->
                    @if (Auth::user()->hasMenuAccess('produtos'))
                        <a href="/admin/produtos" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Produtos') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('empresas'))
                        <a href="/admin/empresas" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Empresas') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('departamentos'))
                        <a href="/admin/departamentos" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Departamentos') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('grupos'))
                        <a href="/admin/grupos" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Grupos') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('configuracao'))
                        <a href="/admin/configuracao" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Configuração') }}
                        </a>
                        <a href="{{ route('admin.web-screen-config.edit') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Config Tela Web') }}
                        </a>
                        <a href="{{ route('admin.organizar-lista.edit') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Organizar Lista') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('configuracao') || Auth::user()->hasMenuAccess('editor_template') || Auth::user()->isDefaultAdmin())
                        @php
                            $galeriaHref = Auth::user()->hasMenuAccess('configuracao')
                                ? route('admin.web-screen-config.edit')
                                : (Auth::user()->hasMenuAccess('editor_template')
                                    ? route('admin.templates.index')
                                    : route('admin.global-image-galleries.index'));
                        @endphp
                        <a href="{{ $galeriaHref }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Galeria') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('token_api'))
                        <a href="{{ route('admin.api-token.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Token API') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('gestao_tvs'))
                        <a href="{{ route('admin.devices.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Gestão de TVs') }}
                        </a>
                    @endif
                    @if (Auth::user()->hasMenuAccess('ativar_tv'))
                        <a href="{{ route('admin.activate-tv.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                            {{ __('Ativar TV') }}
                        </a>
                    @endif
                    @if (Auth::user()->isDefaultAdmin())
                        <a href="{{ route('admin.user-permissions.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
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
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
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

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition ease-in-out duration-150">
                            Deslogar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    @if ($isRevendaNav && $empresaAtivaNav)
        <div class="border-t border-indigo-100 bg-indigo-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
                <p class="text-sm text-indigo-900">
                    Empresa ativa: <strong>{{ $empresaAtivaNav->nome }}</strong>.
                    Todas as alterações feitas em qualquer menu serão aplicadas somente nesta empresa até você trocar a seleção.
                </p>
            </div>
        </div>
    @endif

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (Auth::user()->hasMenuAccess('cadastro_publico'))
                <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register') || request()->routeIs('register.users.*')">
                    {{ __('Cadastro Público') }}
                </x-responsive-nav-link>
            @endif

            <!-- Admin Menu (Mobile) -->
            @if (Auth::user()->hasMenuAccess('produtos'))
                <x-responsive-nav-link href="/admin/produtos">
                    {{ __('Produtos') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('empresas'))
                <x-responsive-nav-link href="/admin/empresas">
                    {{ __('Empresas') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('departamentos'))
                <x-responsive-nav-link href="/admin/departamentos">
                    {{ __('Departamentos') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('grupos'))
                <x-responsive-nav-link href="/admin/grupos">
                    {{ __('Grupos') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('configuracao'))
                <x-responsive-nav-link href="/admin/configuracao">
                    {{ __('Configuração') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.web-screen-config.edit')">
                    {{ __('Config Tela Web') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.organizar-lista.edit')">
                    {{ __('Organizar Lista') }}
                </x-responsive-nav-link>
            @endif
            @if (Auth::user()->hasMenuAccess('configuracao') || Auth::user()->hasMenuAccess('editor_template') || Auth::user()->isDefaultAdmin())
                @php
                    $galeriaHrefMobile = Auth::user()->hasMenuAccess('configuracao')
                        ? route('admin.web-screen-config.edit')
                        : (Auth::user()->hasMenuAccess('editor_template')
                            ? route('admin.templates.index')
                            : route('admin.global-image-galleries.index'));
                @endphp
                <x-responsive-nav-link :href="$galeriaHrefMobile">
                    {{ __('Galeria') }}
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
