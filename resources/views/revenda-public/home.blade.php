@extends('revenda-public.layout')

@section('title', ($page->hero_title ?? $empresa->nome) . ' | Inicio')

@section('content')
    @if ($slides->isNotEmpty())
        <section class="relative overflow-hidden" data-home-carousel>
            @foreach ($slides as $index => $slide)
                <article class="{{ $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none' }} absolute inset-0 transition-opacity duration-700 ease-out" data-slide>
                    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $slide->resolvedImageUrl() }}');"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/65 to-slate-950/15"></div>
                    <div class="relative mx-auto flex min-h-[82vh] max-w-7xl items-end px-6 pb-20 pt-24 lg:px-10">
                        <div class="max-w-2xl">
                            <p class="mb-4 text-xs font-semibold uppercase tracking-[0.45em] text-cyan-300">Revenda personalizada</p>
                            <h1 class="text-4xl font-black tracking-tight text-white md:text-6xl">{{ $slide->title ?: ($page->hero_title ?? $empresa->nome) }}</h1>
                            <p class="mt-6 max-w-xl text-base leading-7 text-white/80 md:text-lg">{{ $slide->subtitle ?: ($page->hero_subtitle ?? '') }}</p>
                            @if ($slide->button_link)
                                <div class="mt-8">
                                    <a href="{{ $slide->button_link }}" class="inline-flex items-center rounded-full bg-cyan-400 px-6 py-3 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 transition hover:bg-cyan-300">{{ $slide->button_label ?: 'Acessar' }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach

            <div class="relative z-20 mx-auto flex min-h-[82vh] max-w-7xl items-end justify-between px-6 pb-10 lg:px-10">
                <div class="flex items-center gap-3" data-carousel-dots>
                    @foreach ($slides as $index => $slide)
                        <button type="button" class="h-3 w-12 rounded-full {{ $index === 0 ? 'bg-cyan-300' : 'bg-white/30' }} transition" data-dot aria-label="Ir para slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        <section class="relative flex min-h-[82vh] items-center overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.2),_transparent_35%),linear-gradient(135deg,_#020617,_#0f172a_55%,_#164e63)]">
            <div class="mx-auto max-w-7xl px-6 py-24 lg:px-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.45em] text-cyan-300">Revenda personalizada</p>
                    <h1 class="mt-6 text-4xl font-black tracking-tight md:text-6xl">{{ $page->hero_title ?? $empresa->nome }}</h1>
                    <p class="mt-6 text-lg leading-8 text-white/75">{{ $page->hero_subtitle ?? 'Esta revenda possui uma pagina publica propria para apresentar sua proposta, diferenciais e contato.' }}</p>
                    @if ($page?->cta_link)
                        <div class="mt-8">
                            <a href="{{ $page->cta_link }}" class="inline-flex items-center rounded-full bg-cyan-400 px-6 py-3 text-sm font-bold uppercase tracking-[0.2em] text-slate-950 transition hover:bg-cyan-300">{{ $page->cta_label ?: 'Acessar' }}</a>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="bg-white text-slate-900">
        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-20 lg:grid-cols-[1.2fr,0.8fr] lg:px-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-700">Sobre</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight">{{ $page->about_title ?? 'Sobre a revenda' }}</h2>
                <div class="mt-6 text-base leading-8 text-slate-700">{!! nl2br(e($page->about_content ?? 'Use esta pagina para destacar a operacao da revenda, mostrar autoridade e gerar novas oportunidades comerciais.')) !!}</div>
            </div>
            <div class="rounded-3xl bg-slate-950 p-8 text-white shadow-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-300">Contato rapido</p>
                <h3 class="mt-3 text-2xl font-black">{{ $page->contact_title ?? 'Fale com nossa equipe' }}</h3>
                <div class="mt-4 space-y-3 text-sm text-white/80">
                    @if ($page?->contact_email)<div><strong>Email:</strong> {{ $page->contact_email }}</div>@endif
                    @if ($page?->contact_phone)<div><strong>Telefone:</strong> {{ $page->contact_phone }}</div>@endif
                    @if ($page?->contact_whatsapp)<div><strong>WhatsApp:</strong> {{ $page->contact_whatsapp }}</div>@endif
                </div>
                @if ($page?->cta_link)
                    <a href="{{ $page->cta_link }}" class="mt-8 inline-flex items-center rounded-full bg-cyan-400 px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-slate-950 transition hover:bg-cyan-300">{{ $page->cta_label ?: 'Entrar em contato' }}</a>
                @endif
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.querySelector('[data-home-carousel]');

            if (!root) {
                return;
            }

            const slides = Array.from(root.querySelectorAll('[data-slide]'));
            const dots = Array.from(root.querySelectorAll('[data-dot]'));
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
                    dot.classList.toggle('bg-cyan-300', dotIndex === currentIndex);
                    dot.classList.toggle('bg-white/30', dotIndex !== currentIndex);
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

            restart();
        });
    </script>
@endsection