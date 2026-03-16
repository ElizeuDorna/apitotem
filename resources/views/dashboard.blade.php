<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $dashboardUser = auth()->user();
        $revendaPrecisaSelecionar = $dashboardUser ? \App\Support\EmpresaContext::requiresSelection($dashboardUser) : false;
        $empresaAtiva = $dashboardUser ? \App\Support\EmpresaContext::activeEmpresa($dashboardUser) : null;
        $temEmpresaAtiva = !empty($empresaAtiva);
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($revendaPrecisaSelecionar && ! $temEmpresaAtiva)
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900">Selecione uma empresa para continuar</h3>
                            <p class="text-sm text-gray-600">Para liberar os menus e configurações, escolha uma empresa cliente da sua revenda.</p>
                            <a href="{{ route('admin.revenda.empresas.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Selecionar Empresa
                            </a>
                        </div>
                    @elseif ($revendaPrecisaSelecionar && $temEmpresaAtiva)
                        <div class="space-y-3">
                            <p class="text-sm text-gray-700">Empresa ativa: <strong>{{ $empresaAtiva->nome }}</strong></p>
                            <p class="text-sm text-gray-600">Este dashboard está reservado para funcionalidades futuras.</p>
                            <a href="{{ route('admin.revenda.empresas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800">
                                Trocar Empresa
                            </a>
                        </div>
                    @else
                        <p>Dashboard disponível. Em breve adicionaremos mais funcionalidades.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
