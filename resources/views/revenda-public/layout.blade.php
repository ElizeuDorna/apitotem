<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $empresa->nome)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-slate-950 text-white antialiased">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-10">
            <div>
                <a href="{{ route('revenda.site.home', ['slug' => $slug]) }}" class="text-lg font-black uppercase tracking-[0.28em] text-white">{{ $empresa->nome }}</a>
                <p class="mt-1 text-xs uppercase tracking-[0.24em] text-cyan-300">Frente publica da revenda</p>
            </div>
            <nav class="hidden md:flex items-center gap-6 text-sm text-white/75">
                <a href="{{ route('revenda.site.home', ['slug' => $slug]) }}" class="hover:text-white">Inicio</a>
                <a href="{{ route('revenda.site.about', ['slug' => $slug]) }}" class="hover:text-white">Sobre</a>
                <a href="{{ route('revenda.site.contact', ['slug' => $slug]) }}" class="hover:text-white">Contato</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-white/10 bg-slate-950">
        <div class="mx-auto max-w-7xl px-6 py-8 text-sm text-white/55 lg:px-10">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>{{ $empresa->nome }}</div>
                <div>Link publico da revenda: {{ request()->getSchemeAndHttpHost() }}/r/{{ $slug }}</div>
            </div>
        </div>
    </footer>
</body>
</html>