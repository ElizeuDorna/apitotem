@extends('revenda-public.layout')

@section('title', ($page->about_title ?? 'Sobre') . ' | ' . $empresa->nome)

@section('content')
    <section class="bg-[linear-gradient(135deg,_#020617,_#0f172a_55%,_#164e63)] text-white">
        <div class="mx-auto max-w-5xl px-6 py-24 lg:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-300">Sobre</p>
            <h1 class="mt-4 text-4xl font-black tracking-tight md:text-5xl">{{ $page->about_title ?? 'Sobre a revenda' }}</h1>
            <div class="mt-8 text-lg leading-8 text-white/80">{!! nl2br(e($page->about_content ?? 'Esta revenda ainda nao cadastrou o conteudo da pagina Sobre.')) !!}</div>
        </div>
    </section>
@endsection