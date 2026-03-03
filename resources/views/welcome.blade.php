<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Meu Site Laravel')</title>
    <!-- Tailwind via CDN para facilitar -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Navbar -->
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-indigo-600">Meu Site Laravel</h1>
            <nav class="space-x-4">
                <a href="{{ url('/') }}" class="hover:text-indigo-600">Início</a>
                <a href="{{ url('/sobre') }}" class="hover:text-indigo-600">Sobre</a>
                <a href="{{ url('/contato') }}" class="hover:text-indigo-600">Contato</a>
            </nav>
        </div>
    </header>

    <!-- Conteúdo -->
    <main class="max-w-6xl mx-auto px-4 py-10">
        @yield('content')
    </main>

    <!-- Rodapé -->
    <footer class="bg-white shadow mt-10">
        <div class="max-w-6xl mx-auto px-4 py-6 text-center text-gray-500">
            &copy; {{ date('Y') }} Meu Site Laravel. Todos os direitos reservados.
        </div>
    </footer>

</body>
</html>
