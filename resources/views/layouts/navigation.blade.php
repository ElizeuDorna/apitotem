<div>
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
        $socialMediaMenuActive = request()->is('admin/rede-social*');
    @endphp

    @php
        $desktopNavBase = 'flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white';
        $desktopNavActive = 'bg-slate-800 text-white ring-1 ring-slate-700';
        $panelBrandIconUrl = is_string($panelBrandIconUrl ?? null) ? trim($panelBrandIconUrl) : '';
    @endphp

    @php
        $sidebarInlineStyles = [];
        $panelSidebarFontFamilyCss = is_string($panelSidebarFontFamilyCss ?? null) ? trim($panelSidebarFontFamilyCss) : '';
        $panelSidebarFontSizeCss = is_string($panelSidebarFontSizeCss ?? null) ? trim($panelSidebarFontSizeCss) : '';

        if ($panelSidebarFontFamilyCss !== '') {
            $sidebarInlineStyles[] = '--panel-sidebar-font-family: '.$panelSidebarFontFamilyCss;
        }

        if ($panelSidebarFontSizeCss !== '') {
            $sidebarInlineStyles[] = '--panel-sidebar-font-size: '.$panelSidebarFontSizeCss;
        }
    @endphp

    <aside class="panel-sidebar fixed inset-y-0 left-0 z-40 flex h-screen w-56 flex-col overflow-hidden border-r border-slate-800 bg-slate-950 text-white shadow-2xl" @if($sidebarInlineStyles !== []) style="{{ implode(';', $sidebarInlineStyles) }}" @endif>
        <div class="border-b border-slate-800 px-3 py-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl transition">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-800 text-white shadow-lg shadow-slate-950/30">
                    @if ($panelBrandIconUrl !== '')
                        <img src="{{ $panelBrandIconUrl }}" alt="Ícone do painel" class="block h-5 w-5 object-contain" />
                    @else
                        <x-application-logo class="block h-5 w-auto fill-current text-white" />
                    @endif
                </div>
                <div>
                    <div class="text-[11px] font-black uppercase tracking-[0.14em] text-white">Totem</div>
                    <div class="text-[9px] uppercase tracking-[0.12em] text-slate-400">Painel administrativo</div>
                </div>
            </a>
        </div>

        @if ($isRevendaNav && $empresaAtivaNav)
            <div class="border-b border-emerald-900 bg-emerald-950/70 px-3 py-2.5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-300">Empresa ativa</p>
                <p class="mt-1 text-[11px] font-semibold text-white">{{ $empresaAtivaNav->nome }}</p>
            </div>
        @endif

        <div class="min-h-0 flex flex-1 flex-col px-4 py-2.5">
            <div class="min-h-0 flex-1 overflow-y-scroll rounded-xl border border-slate-800 bg-slate-900/40 p-1.5 pr-1 [scrollbar-color:#475569_#0f172a] [scrollbar-gutter:stable]">
                <div class="space-y-0.5">
                <a href="{{ route('dashboard') }}" class="{{ $desktopNavBase }} {{ request()->routeIs('dashboard') ? $desktopNavActive : '' }}">
                    <span>{{ __('Dashboard') }}</span>
                </a>

                @if (Auth::user()->hasMenuAccess('cadastro_publico'))
                    <a href="{{ route('register') }}" class="{{ $desktopNavBase }} {{ (request()->routeIs('register') || request()->routeIs('register.users.*')) ? $desktopNavActive : '' }}">
                        <span>{{ __('Cadastro Usuarios') }}</span>
                    </a>
                @endif

                @if ($hasCadastroMenu)
                    <div x-data="{ cadastroOpen: {{ $cadastroMenuActive ? 'true' : 'false' }} }" class="rounded-lg border border-slate-800 bg-slate-900/60 px-1.5 py-1">
                        <button
                            type="button"
                            @click="cadastroOpen = !cadastroOpen"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-[11px] font-semibold text-slate-200 transition hover:bg-slate-800"
                        >
                            <span>{{ __('Cadastro') }}</span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': cadastroOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.512a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-cloak x-show="cadastroOpen" class="mt-1 space-y-0.5 border-t border-slate-800 pt-1">
                            @if ($navUser->hasMenuAccess('produtos'))
                                <a href="/admin/produtos" class="{{ $desktopNavBase }} ml-2 {{ request()->is('admin/produtos*') ? $desktopNavActive : '' }}">
                                    <span>{{ __('Produtos') }}</span>
                                </a>
                            @endif
                            @if ($navUser->hasMenuAccess('departamentos'))
                                <a href="/admin/departamentos" class="{{ $desktopNavBase }} ml-2 {{ request()->is('admin/departamentos*') ? $desktopNavActive : '' }}">
                                    <span>{{ __('Departamentos') }}</span>
                                </a>
                            @endif
                            @if ($navUser->hasMenuAccess('grupos'))
                                <a href="/admin/grupos" class="{{ $desktopNavBase }} ml-2 {{ request()->is('admin/grupos*') ? $desktopNavActive : '' }}">
                                    <span>{{ __('Grupos') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if (Auth::user()->hasMenuAccess('empresas'))
                    <a href="/admin/empresas" class="{{ $desktopNavBase }} {{ request()->is('admin/empresas*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Empresas') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('financeiro'))
                    <a href="{{ route('admin.financeiro.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/financeiro*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Financeiro') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('config_admin'))
                    <a href="{{ route('admin.configadmin.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/configadmin') ? $desktopNavActive : '' }}">
                        <span>{{ __('Config Admin') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('downloads'))
                    <a href="{{ route('admin.downloads.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/downloads*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Download') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('rede_social'))
                    <div x-data="{ socialMediaOpen: {{ $socialMediaMenuActive ? 'true' : 'false' }} }" class="rounded-lg border border-slate-800 bg-slate-900/60 px-1.5 py-1">
                        <button
                            type="button"
                            @click="socialMediaOpen = !socialMediaOpen"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-[11px] font-semibold text-slate-200 transition hover:bg-slate-800"
                        >
                            <span>{{ __('Rede Social') }}</span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': socialMediaOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.512a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-cloak x-show="socialMediaOpen" class="mt-1 space-y-0.5 border-t border-slate-800 pt-1">
                            @if ($navUser->hasMenuAccess('rede_social_meta'))
                                <a href="{{ route('admin.social-media.index') }}" class="{{ $desktopNavBase }} ml-2 {{ request()->routeIs('admin.social-media.index') ? $desktopNavActive : '' }}">
                                    <span>{{ __('Facebook / Instagram') }}</span>
                                </a>
                            @endif
                            @if ($navUser->hasMenuAccess('rede_social_whatsapp'))
                                <a href="{{ route('admin.social-media.whatsapp.index') }}" class="{{ $desktopNavBase }} ml-2 {{ request()->routeIs('admin.social-media.whatsapp.index') ? $desktopNavActive : '' }}">
                                    <span>{{ __('WhatsApp') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if (Auth::user()->hasMenuAccess('config_tela_web'))
                    <a href="{{ route('admin.web-screen-config.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/configuracao-tela-web') ? $desktopNavActive : '' }}">
                        <span>{{ __('Config Totem Web') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('organizar_lista'))
                    <a href="{{ route('admin.organizar-lista.edit') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/organizar-lista') ? $desktopNavActive : '' }}">
                        <span>{{ __('Organizar Lista') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('galeria_nova'))
                    <a href="{{ route('admin.galeria-imagem.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/galeria-imagem*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Galeria de Imagem') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('token_api'))
                    <a href="{{ route('admin.api-token.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/api-token*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Token API') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('gestao_tvs'))
                    <a href="{{ route('admin.devices.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/devices*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Gestão de TVs') }}</span>
                    </a>
                @endif

                @if (Auth::user()->hasMenuAccess('ativar_tv'))
                    <a href="{{ route('admin.activate-tv.index') }}" class="{{ $desktopNavBase }} {{ request()->is('admin/ativar-tv*') ? $desktopNavActive : '' }}">
                        <span>{{ __('Ativar TV') }}</span>
                    </a>
                @endif

                @if (Auth::user()->isDefaultAdmin())
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
        </div>

        <div class="border-t border-slate-800 px-3 py-2.5">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-2.5">
                <div class="text-xs font-semibold text-white">{{ $navUser->name }}</div>
                <div class="mt-1 truncate text-[9px] text-slate-400">{{ $navUser->email }}</div>

                <div class="mt-2.5 flex flex-col gap-1">
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-[11px] font-medium text-white transition hover:bg-slate-700">
                        {{ __('Profile') }}
                    </a>

                    @if ($isRevendaNav)
                        <a href="{{ route('admin.empresas.index') }}" class="inline-flex items-center justify-center rounded-lg border border-indigo-900 bg-indigo-900/60 px-2.5 py-1.5 text-[11px] font-medium text-indigo-100 transition hover:bg-indigo-800">
                            TrocarEmp
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-rose-900 bg-rose-900/60 px-2.5 py-1.5 text-[11px] font-medium text-rose-100 transition hover:bg-rose-800">
                            Deslogar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    @if ($isRevendaNav && $empresaAtivaNav)
        <div class="sr-only">
            Empresa ativa: {{ $empresaAtivaNav->nome }}
        </div>
    @endif
</div>