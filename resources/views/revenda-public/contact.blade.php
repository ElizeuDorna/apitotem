@extends('revenda-public.layout')

@section('title', ($page->contact_title ?? 'Contato') . ' | ' . $empresa->nome)

@section('content')
    <section class="bg-white text-slate-900">
        <div class="mx-auto max-w-5xl px-6 py-24 lg:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-700">Contato</p>
            <h1 class="mt-4 text-4xl font-black tracking-tight md:text-5xl">{{ $page->contact_title ?? 'Fale com a revenda' }}</h1>
            <div class="mt-8 text-lg leading-8 text-slate-700">{!! nl2br(e($page->contact_content ?? 'Use os canais abaixo para entrar em contato com a revenda.')) !!}</div>

            <div class="mt-10 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Email</div>
                    <div class="mt-3 text-base font-semibold text-slate-900">{{ $page->contact_email ?: 'Nao informado' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Telefone</div>
                    <div class="mt-3 text-base font-semibold text-slate-900">{{ $page->contact_phone ?: 'Nao informado' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">WhatsApp</div>
                    <div class="mt-3 text-base font-semibold text-slate-900">{{ $page->contact_whatsapp ?: 'Nao informado' }}</div>
                </div>
            </div>

            @if ($page?->cta_link)
                <div class="mt-10">
                    <a href="{{ $page->cta_link }}" class="inline-flex items-center rounded-full bg-slate-950 px-6 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white transition hover:bg-slate-800">{{ $page->cta_label ?: 'Entrar em contato' }}</a>
                </div>
            @endif
        </div>
    </section>
@endsection