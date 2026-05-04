<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $layoutAuthUser = auth()->user();
            $layoutEmpresaId = $layoutAuthUser ? \App\Support\EmpresaContext::resolveEmpresaIdForUser($layoutAuthUser) : null;
            $panelBrandIconUrl = '';

            $hasPanelBrandIconColumn = \Illuminate\Support\Facades\Schema::hasColumn('configuracoes', 'panelBrandIconUrl');

            if ($layoutEmpresaId && $hasPanelBrandIconColumn) {
                $panelBrandIconUrl = (string) (\App\Models\Configuracao::query()
                    ->where('empresa_id', (int) $layoutEmpresaId)
                    ->value('panelBrandIconUrl') ?? '');
            }
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ $panelBrandIconUrl !== '' ? $panelBrandIconUrl : asset('favicon.ico') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script>
            (() => {
                try {
                    const savedTheme = localStorage.getItem('panel-theme');
                    document.documentElement.dataset.panelTheme = savedTheme === 'dark' ? 'dark' : 'light';
                } catch (error) {
                    document.documentElement.dataset.panelTheme = 'light';
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        x-data="{
            panelTheme: 'light',
            init() {
                try {
                    const savedTheme = localStorage.getItem('panel-theme');
                    this.panelTheme = savedTheme === 'dark' ? 'dark' : 'light';
                } catch (error) {
                    this.panelTheme = 'light';
                }

                this.applyPanelTheme();
            },
            togglePanelTheme() {
                this.panelTheme = this.panelTheme === 'dark' ? 'light' : 'dark';
                this.applyPanelTheme();
            },
            applyPanelTheme() {
                document.documentElement.dataset.panelTheme = this.panelTheme;

                try {
                    localStorage.setItem('panel-theme', this.panelTheme);
                } catch (error) {
                }
            }
        }"
        x-init="init()"
        class="panel-auth-body bg-slate-100 font-sans antialiased text-slate-900"
        :data-panel-theme="panelTheme"
    >
        @php
            $layoutEmpresaAtiva = $layoutAuthUser ? \App\Support\EmpresaContext::activeEmpresa($layoutAuthUser) : null;
            $layoutExigeEmpresaAtiva = $layoutAuthUser ? \App\Support\EmpresaContext::requiresSelection($layoutAuthUser) : false;
        @endphp

        <div class="panel-auth-shell min-h-screen bg-slate-100">
            @include('layouts.navigation', ['panelBrandIconUrl' => $panelBrandIconUrl])

            <div class="panel-auth-content ml-64 min-w-0">
                @isset($header)
                    <header class="panel-auth-header border-b border-slate-200 bg-white shadow-sm">
                        <div class="px-3 py-5 sm:px-4 lg:px-6">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                @if ($layoutExigeEmpresaAtiva && $layoutEmpresaAtiva)
                    <div class="px-3 pt-4 sm:px-4 lg:px-6">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                            <span class="font-semibold">Empresa selecionada:</span>
                            <span class="ml-1">{{ $layoutEmpresaAtiva->nome }}</span>
                        </div>
                    </div>
                @endif

                <main class="panel-auth-main px-3 py-6 sm:px-4 lg:px-6">
                    <div class="lg:pl-4 [&>div.py-6]:!pt-0 [&>div.py-8]:!pt-0 [&>div.py-10]:!pt-0 [&>div.py-6]:!mt-0 [&>div.py-8]:!mt-0 [&>div.py-10]:!mt-0 [&>div>div.mx-auto]:!ml-0 [&>div>div.mx-auto]:!mr-auto">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
