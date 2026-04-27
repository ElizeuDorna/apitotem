<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Upload APK Android
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if (session('status'))
                        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="rounded-xl border border-sky-100 bg-sky-50/70 p-5">
                        <h3 class="text-lg font-semibold text-slate-900">Publicação do APK</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            Envie o APK da WebView Android. O sistema publica sempre com o nome fixo <strong>install.apk</strong>.
                        </p>
                        <p class="mt-2 text-sm text-slate-600">
                            Download público sem login: <a href="{{ $apkDownloadUrl }}" target="_blank" class="font-semibold text-indigo-700 hover:text-indigo-900">{{ $apkDownloadUrl }}</a>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h3 class="text-base font-semibold text-slate-900">Arquivo atual</h3>

                        @if ($apkExists)
                            <div class="mt-3 space-y-2 text-sm text-slate-700">
                                <p><strong>Nome:</strong> install.apk</p>
                                <p><strong>Tamanho:</strong> {{ number_format(($apkSizeBytes ?? 0) / 1048576, 2, ',', '.') }} MB</p>
                                <p><strong>Atualizado em:</strong> {{ $apkLastModified ? date('d/m/Y H:i', $apkLastModified) : '-' }}</p>
                            </div>
                        @else
                            <p class="mt-3 text-sm text-amber-700">Nenhum APK foi publicado ainda.</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h3 class="text-base font-semibold text-slate-900">Enviar novo APK</h3>

                        <form method="POST" action="{{ route('admin.apk-upload.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf

                            <div>
                                <label for="apk_file" class="block text-sm font-medium text-gray-700 mb-1">Arquivo APK</label>
                                <input id="apk_file" name="apk_file" type="file" accept=".apk,application/vnd.android.package-archive" class="w-full border rounded px-3 py-2 bg-white text-sm">
                                <p class="mt-1 text-xs text-gray-500">O arquivo enviado será salvo e publicado como install.apk.</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <button type="submit" class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Enviar APK
                                </button>
                                <a href="{{ $apkDownloadUrl }}" target="_blank" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">
                                    Testar download público
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>