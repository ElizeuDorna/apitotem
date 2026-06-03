@php
    $publicPanelBrandIconUrl = '';
    $publicAuthUser = auth()->user();
    $publicEmpresaId = $publicAuthUser ? \App\Support\EmpresaContext::resolveEmpresaIdForUser($publicAuthUser) : null;
    $publicIsDefaultAdmin = $publicAuthUser && $publicAuthUser->isDefaultAdmin();
    $publicUseGlobalConfig = ! $publicEmpresaId && (! $publicAuthUser || $publicIsDefaultAdmin);
    $publicHasPanelBrandIconColumn = \Illuminate\Support\Facades\Schema::hasColumn('configuracoes', 'panelBrandIconUrl');

    if ($publicHasPanelBrandIconColumn) {
        if ($publicEmpresaId) {
            $publicPanelBrandIconUrl = (string) (\App\Models\Configuracao::query()
                ->where('empresa_id', (int) $publicEmpresaId)
                ->value('panelBrandIconUrl') ?? '');
        } elseif ($publicUseGlobalConfig) {
            $publicPanelBrandIconUrl = (string) (\App\Models\Configuracao::query()
                ->whereNull('empresa_id')
                ->value('panelBrandIconUrl') ?? '');
        }
    }
@endphp
<link rel="icon" href="{{ $publicPanelBrandIconUrl !== '' ? $publicPanelBrandIconUrl : asset('favicon.ico') }}">