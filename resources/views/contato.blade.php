@extends('layouts.app')

@section('title', 'Contato')

@section('content')
    <h2 class="text-3xl font-bold mb-4">Fale Conosco</h2>
    <form action="#" method="POST" class="max-w-md space-y-4">
        @csrf
        <input type="text" name="nome" placeholder="Seu nome"
               class="w-full px-4 py-2 border rounded-lg">
        <input type="email" name="email" placeholder="Seu e-mail"
               class="w-full px-4 py-2 border rounded-lg">
        <textarea name="mensagem" placeholder="Sua mensagem"
                  class="w-full px-4 py-2 border rounded-lg"></textarea>
        <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            Enviar
        </button>
    </form>
@endsection
