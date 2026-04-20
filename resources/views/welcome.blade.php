<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela Inicial</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white">
    <header class="absolute inset-x-0 top-0 z-30">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-10">
            <div>
                <a href="{{ route('home') }}" class="text-xl font-black tracking-[0.25em] uppercase text-white">Totem</a>
            </div>
            <nav class="hidden md:flex items-center gap-6 text-sm text-white/80">
                <a href="{{ url('/') }}" class="hover:text-white">Início</a>
                <a href="{{ url('/sobre') }}" class="hover:text-white">Sobre</a>
                <a href="{{ url('/contato') }}" class="hover:text-white">Contato</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full border border-white/20 px-4 py-2 hover:bg-white/10">Admin</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @if ($slides->isNotEmpty())
            <section class="relative overflow-hidden" data-home-carousel>
                @foreach ($slides as $index => $slide)
                    <article
                        class="{{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none' }} absolute inset-0 transition-opacity duration-700 ease-out"
                        data-slide
                    >
                        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $slide->resolvedImageUrl() }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/65 to-slate-950/15"></div>
                        <div class="relative mx-auto flex min-h-screen max-w-7xl items-end px-6 pb-24 pt-32 lg:px-10 lg:pb-28">
                            <div class="max-w-2xl">
                                <p class="mb-4 text-xs font-semibold uppercase tracking-[0.45em] text-cyan-300">Tela inicial dinâmica</p>
                                <h1 class="text-4xl font-black tracking-tight text-white md:text-6xl">{{ $slide->title ?: 'Seu conteúdo em destaque' }}</h1>
                                @if ($slide->subtitle)
                                    <p class="mt-6 max-w-xl text-base leading-7 text-white/80 md:text-lg">{{ $slide->subtitle }}</p>
                                @endif
                                @if ($slide->button_link)
                                    <div class="mt-8">
                                        <a href="{{ $slide->button_link }}" class="inline-flex items-center rounded-full bg-cyan-400 px-6 py-3 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 transition hover:bg-cyan-300">
                                            {{ $slide->button_label ?: 'Acessar' }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach

                <div class="relative z-20 mx-auto flex min-h-screen max-w-7xl items-end justify-between px-6 pb-10 lg:px-10">
                    <div class="flex items-center gap-3" data-carousel-dots>
                        @foreach ($slides as $index => $slide)
                            <button type="button" class="h-3 w-12 rounded-full {{ $index === 0 ? 'bg-cyan-300' : 'bg-white/30' }} transition" data-dot aria-label="Ir para slide {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                    @if ($slides->count() > 1)
                        <div class="flex items-center gap-3">
                            <button type="button" class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20" data-prev>Anterior</button>
                            <button type="button" class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20" data-next>Próximo</button>
                        </div>
                    @endif
                </div>
            </section>
        @else
            <section class="relative flex min-h-screen items-center overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.2),_transparent_35%),linear-gradient(135deg,_#020617,_#0f172a_55%,_#164e63)]">
                <div class="mx-auto max-w-7xl px-6 py-24 lg:px-10">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.45em] text-cyan-300">Tela inicial</p>
                        <h1 class="mt-6 text-4xl font-black tracking-tight md:text-6xl">Seu carrossel ainda não foi cadastrado.</h1>
                        <p class="mt-6 text-lg leading-8 text-white/75">Entre no painel administrativo como admin padrão, abra o menu Carrossel Inicial e crie os primeiros slides com upload ou link direto da internet.</p>
                        @auth
                            <div class="mt-8">
                                <a href="{{ route('admin.home-carousel.index') }}" class="inline-flex items-center rounded-full bg-cyan-400 px-6 py-3 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 transition hover:bg-cyan-300">Abrir gerenciamento</a>
                            </div>
                        @endauth
                    </div>
                </div>
            </section>
        @endif
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.querySelector('[data-home-carousel]');

            if (!root) {
                return;
            }

            const slides = Array.from(root.querySelectorAll('[data-slide]'));
            const dots = Array.from(root.querySelectorAll('[data-dot]'));
            const nextButton = root.querySelector('[data-next]');
            const prevButton = root.querySelector('[data-prev]');
            let currentIndex = 0;
            let timer = null;

            const showSlide = (index) => {
                currentIndex = (index + slides.length) % slides.length;

                slides.forEach((slide, slideIndex) => {
                    const active = slideIndex === currentIndex;
                    slide.classList.toggle('opacity-100', active);
                    slide.classList.toggle('z-10', active);
                    slide.classList.toggle('pointer-events-none', !active);
                    slide.classList.toggle('opacity-0', !active);
                    slide.classList.toggle('z-0', !active);
                });

                dots.forEach((dot, dotIndex) => {
                    const active = dotIndex === currentIndex;
                    dot.classList.toggle('bg-cyan-300', active);
                    dot.classList.toggle('bg-white/30', !active);
                });
            };

            const restart = () => {
                if (timer) {
                    window.clearInterval(timer);
                }

                if (slides.length > 1) {
                    timer = window.setInterval(() => showSlide(currentIndex + 1), 5000);
                }
            };

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    showSlide(index);
                    restart();
                });
            });

            if (nextButton) {
                nextButton.addEventListener('click', () => {
                    showSlide(currentIndex + 1);
                    restart();
                });
            }

            if (prevButton) {
                prevButton.addEventListener('click', () => {
                    showSlide(currentIndex - 1);
                    restart();
                });
            }

            restart();
        });
    </script>
</body>
</html>
