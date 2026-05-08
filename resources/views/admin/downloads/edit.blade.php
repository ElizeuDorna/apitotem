<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Download
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.downloads.update', $downloadAsset) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        @include('admin.downloads._form', ['downloadAsset' => $downloadAsset])

                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.downloads.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Voltar</a>
                            <x-primary-button>Atualizar arquivo</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>