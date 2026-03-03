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
            <nav class="hidden md:flex space-x-4 items-center">
                <a href="{{ url('/') }}" class="hover:text-indigo-600">Início</a>
                <a href="{{ url('/sobre') }}" class="hover:text-indigo-600">Sobre</a>
                <a href="{{ url('/contato') }}" class="hover:text-indigo-600">Contato</a>
                <div class="inline-block relative group">
                    <button class="hover:text-indigo-600">Admin ▼</button>
                    <div class="absolute hidden group-hover:block bg-white shadow z-10 min-w-48">
                        <a href="{{ route('admin.produtos.index') }}" class="block px-4 py-2 hover:bg-gray-100">Produtos</a>
                        <a href="{{ route('admin.empresas.index') }}" class="block px-4 py-2 hover:bg-gray-100">Empresas</a>
                        <a href="{{ route('admin.departamentos.index') }}" class="block px-4 py-2 hover:bg-gray-100">Departamentos</a>
                        <a href="{{ route('admin.grupos.index') }}" class="block px-4 py-2 hover:bg-gray-100">Grupos</a>
                        <a href="{{ url('/admin/configuracao') }}" class="block px-4 py-2 hover:bg-gray-100">Configuração</a>
                    </div>
                </div>
            </nav>

            <details class="md:hidden relative">
                <summary class="list-none cursor-pointer px-3 py-2 border rounded text-sm font-semibold">Menu</summary>
                <div class="absolute right-0 mt-2 w-64 bg-white border rounded shadow z-20 p-2 space-y-1">
                    <a href="{{ url('/') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Início</a>
                    <a href="{{ url('/sobre') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Sobre</a>
                    <a href="{{ url('/contato') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Contato</a>
                    <div class="px-3 pt-2 pb-1 text-xs font-bold uppercase text-gray-500">Admin</div>
                    <a href="{{ route('admin.produtos.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Produtos</a>
                    <a href="{{ route('admin.empresas.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Empresas</a>
                    <a href="{{ route('admin.departamentos.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Departamentos</a>
                    <a href="{{ route('admin.grupos.index') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Grupos</a>
                    <a href="{{ url('/admin/configuracao') }}" class="block px-3 py-2 rounded hover:bg-gray-100">Configuração</a>
                </div>
            </details>
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
