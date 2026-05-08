<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Downloads Públicos</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 p-8 text-white shadow-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-200">Área pública</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight">Downloads disponíveis</h1>
                <p class="mt-3 max-w-2xl text-sm text-slate-200">
                    Nesta página ficam publicados os arquivos liberados para download. Escolha o item desejado e baixe a versão mais recente.
                </p>
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-2">
                @forelse ($downloads as $download)
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">{{ $download->title }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $download->original_name }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                {{ number_format($download->size_bytes / 1048576, 2, ',', '.') }} MB
                            </span>
                        </div>

                        @if ($download->description)
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $download->description }}</p>
                        @endif

                        <div class="mt-5 flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Atualizado em {{ $download->updated_at?->format('d/m/Y H:i') }}</span>
                            <a href="{{ route('downloads.file', $download) }}" class="inline-flex items-center rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                Baixar arquivo
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">
                        Nenhum download público está disponível no momento.
                    </div>
                @endforelse
            </div>
        </main>
    </body>
</html>