<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RevendaSiteController;
use App\Models\Device;
use App\Models\Empresa;
use App\Models\Produto;
use App\Support\EmpresaContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', HomeController::class)->name('home');

Route::get('/sobre', function () {
    return view('sobre');
});

Route::get('/contato', function () {
    return view('contato');
});

Route::get('/r/{slug}', [RevendaSiteController::class, 'home'])->name('revenda.site.home');
Route::get('/r/{slug}/sobre', [RevendaSiteController::class, 'about'])->name('revenda.site.about');
Route::get('/r/{slug}/contato', [RevendaSiteController::class, 'contact'])->name('revenda.site.contact');

Route::view('/tv/totemweb', 'tv.produtos')->name('tv.totemweb');
Route::view('/tv/totemweb/configuracao', 'tv.configuracao')->name('tv.totemweb.configuracao');
Route::redirect('/tv/telaweb01', '/tv/totemweb');
Route::redirect('/tv/telaweb01/configuracao', '/tv/totemweb/configuracao');
Route::redirect('/tv/produtos', '/tv/totemweb');
Route::redirect('/tv/configuracao', '/tv/totemweb/configuracao');

Route::get('/dashboard', function () {
    $user = auth()->user();

    $isAdminGeral = $user && $user->isDefaultAdmin();
    $revendaPrecisaSelecionar = $user ? EmpresaContext::requiresSelection($user) : false;
    $empresaAtiva = $user ? EmpresaContext::activeEmpresa($user) : null;
    $temEmpresaAtiva = ! empty($empresaAtiva);
    $empresaRevendaId = $revendaPrecisaSelecionar && $user && $user->empresa ? (int) $user->empresa->id : null;

    $empresaQuery = Empresa::query();
    $escopoTitulo = 'Resumo geral da operacao';

    if ($isAdminGeral) {
        $empresaQuery->where('nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL);
        $escopoTitulo = 'Visao geral de revendas e clientes finais';
    } elseif ($revendaPrecisaSelecionar && $empresaRevendaId) {
        $empresaQuery
            ->where('revenda_id', $empresaRevendaId)
            ->where('nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL);
        $escopoTitulo = 'Visao dos clientes da sua revenda';
    } elseif ($user && $user->empresa_id) {
        $empresaQuery->where('id', (int) $user->empresa_id);
        $escopoTitulo = 'Visao da sua empresa';
    } else {
        $empresaQuery->whereRaw('1 = 0');
    }

    $empresaIds = (clone $empresaQuery)->pluck('id');

    $revendasTotal = Empresa::query()
        ->where('nivel_acesso', Empresa::NIVEL_REVENDA)
        ->count();

    $revendasAtivas = $revendasTotal;
    if (Schema::hasColumn('empresa', 'ativo')) {
        $revendasAtivas = Empresa::query()
            ->where('nivel_acesso', Empresa::NIVEL_REVENDA)
            ->where('ativo', 1)
            ->count();
    }

    $clientesVinculadosRevenda = Empresa::query()
        ->where('nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL)
        ->whereNotNull('revenda_id')
        ->count();

    $clientesFinaisTotal = Empresa::query()
        ->where('nivel_acesso', Empresa::NIVEL_CLIENTE_FINAL)
        ->count();

    $clientesTotal = $empresaIds->count();

    $clientesAtivos = $clientesTotal;
    if (Schema::hasColumn('empresa', 'ativo')) {
        $clientesAtivos = Empresa::query()
            ->whereIn('id', $empresaIds)
            ->where('ativo', 1)
            ->count();
    }

    $tvsBaseQuery = Device::query()->whereIn('empresa_id', $empresaIds);

    $tvsTotal = (clone $tvsBaseQuery)->count();
    $tvsAtivas = (clone $tvsBaseQuery)->where('ativo', true)->count();
    $tvsOnline = (clone $tvsBaseQuery)
        ->where('ativo', true)
        ->whereNotNull('last_seen_at')
        ->where('last_seen_at', '>=', now()->subMinutes(2))
        ->count();

    $produtosTotal = Produto::query()
        ->whereIn('empresa_id', $empresaIds)
        ->count();

    return view('dashboard', [
        'isAdminGeral' => $isAdminGeral,
        'revendaPrecisaSelecionar' => $revendaPrecisaSelecionar,
        'empresaAtiva' => $empresaAtiva,
        'temEmpresaAtiva' => $temEmpresaAtiva,
        'escopoTitulo' => $escopoTitulo,
        'revendasTotal' => $revendasTotal,
        'revendasAtivas' => $revendasAtivas,
        'clientesVinculadosRevenda' => $clientesVinculadosRevenda,
        'clientesFinaisTotal' => $clientesFinaisTotal,
        'clientesTotal' => $clientesTotal,
        'clientesAtivos' => $clientesAtivos,
        'tvsTotal' => $tvsTotal,
        'tvsAtivas' => $tvsAtivas,
        'tvsOnline' => $tvsOnline,
        'produtosTotal' => $produtosTotal,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dw', [\App\Http\Controllers\Admin\DownloadAssetController::class, 'publicIndex'])
    ->name('downloads.public.index');
Route::get('/dw/{downloadAsset:slug}', [\App\Http\Controllers\Admin\DownloadAssetController::class, 'download'])
    ->name('downloads.file');

Route::get('/webhooks/whatsapp', [\App\Http\Controllers\Admin\WhatsAppWebhookController::class, 'verify'])
    ->name('whatsapp.webhook.verify');
Route::post('/webhooks/whatsapp', [\App\Http\Controllers\Admin\WhatsAppWebhookController::class, 'receive'])
    ->name('whatsapp.webhook.receive');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('admin/home-carousel', \App\Http\Controllers\Admin\HomeCarouselController::class, ['as' => 'admin'])
        ->parameters(['home-carousel' => 'homeCarousel'])
        ->except(['show']);
    Route::get('/admin/revenda/frente-publica', [\App\Http\Controllers\Admin\RevendaPublicPageController::class, 'edit'])
        ->name('admin.revenda-public-page.edit');
    Route::put('/admin/revenda/frente-publica', [\App\Http\Controllers\Admin\RevendaPublicPageController::class, 'update'])
        ->name('admin.revenda-public-page.update');
    Route::resource('/admin/revenda/frente-publica/slides', \App\Http\Controllers\Admin\RevendaPublicPageSlideController::class)
        ->parameters(['slides' => 'revendaPublicPageSlide'])
        ->names('admin.revenda-public-page-slides')
        ->except(['show']);

    Route::get('/admin/revenda/empresas', [\App\Http\Controllers\Admin\RevendaEmpresaContextController::class, 'index'])
        ->name('admin.revenda.empresas.index')
        ->middleware('menu.access:empresas');
    Route::post('/admin/revenda/empresas/{empresa}/acessar', [\App\Http\Controllers\Admin\RevendaEmpresaContextController::class, 'acessar'])
        ->name('admin.revenda.empresas.acessar')
        ->middleware('menu.access:empresas');

    Route::middleware('revenda.empresa.selecionada')->group(function () {

    // Admin CRUDs - protegidas por autenticação
    Route::get('/admin/configadmin', [\App\Http\Controllers\Admin\ConfigAdminController::class, 'edit'])
        ->name('admin.configadmin.edit')
        ->middleware('menu.access:config_admin');
    Route::post('/admin/configadmin', [\App\Http\Controllers\Admin\ConfigAdminController::class, 'update'])
        ->name('admin.configadmin.update')
        ->middleware('menu.access:config_admin');
        Route::get('/admin/downloads', [\App\Http\Controllers\Admin\DownloadAssetController::class, 'index'])
            ->name('admin.downloads.index')
            ->middleware('menu.access:downloads');
        Route::get('/admin/downloads/{downloadAsset:slug}/baixar', [\App\Http\Controllers\Admin\DownloadAssetController::class, 'adminDownload'])
            ->name('admin.downloads.download')
            ->middleware('menu.access:downloads');
        Route::get('/admin/configuracao-tela-web', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'edit'])
            ->name('admin.web-screen-config.edit')
            ->middleware('menu.access:config_tela_web');
        Route::post('/admin/configuracao-tela-web', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'update'])
            ->name('admin.web-screen-config.update')
            ->middleware('menu.access:config_tela_web');
        Route::get('/admin/tvpreview', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'previewIndex'])
            ->name('admin.tvpreview.index')
            ->middleware('menu.access:config_tela_web');
        Route::get('/admin/tvpreview/tela', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'preview'])
            ->name('admin.tvpreview.show')
            ->middleware('menu.access:config_tela_web');
        Route::get('/admin/tvpreview/config', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'previewConfig'])
            ->name('admin.tvpreview.config')
            ->middleware('menu.access:config_tela_web');
        Route::get('/admin/tvpreview/produtos', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'previewProducts'])
            ->name('admin.tvpreview.products')
            ->middleware('menu.access:config_tela_web');
        Route::get('/admin/tvpreview/midias', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'previewMedia'])
            ->name('admin.tvpreview.media')
            ->middleware('menu.access:config_tela_web');
        Route::get('/admin/global-image-galleries/lookup/{code}', [\App\Http\Controllers\Admin\GlobalImageGalleryController::class, 'lookupByCode'])
            ->name('admin.global-image-galleries.lookup')
            ->middleware('menu.access:configuracao');
        Route::get('/admin/global-image-galleries/search-by-name', [\App\Http\Controllers\Admin\GlobalImageGalleryController::class, 'searchByName'])
            ->name('admin.global-image-galleries.search-by-name')
            ->middleware('menu.access:configuracao');
        Route::get('/admin/organizar-lista', [\App\Http\Controllers\Admin\OrganizarListaController::class, 'edit'])
            ->name('admin.organizar-lista.edit')
            ->middleware('menu.access:organizar_lista');
        Route::post('/admin/organizar-lista', [\App\Http\Controllers\Admin\OrganizarListaController::class, 'update'])
            ->name('admin.organizar-lista.update')
            ->middleware('menu.access:organizar_lista');

    Route::prefix('admin')->group(function () {
        Route::get('devices', [\App\Http\Controllers\Admin\DeviceManagementController::class, 'index'])
            ->name('admin.devices.index')
            ->middleware('menu.access:gestao_tvs');
        Route::get('devices/{device}/edit', [\App\Http\Controllers\Admin\DeviceManagementController::class, 'edit'])
            ->name('admin.devices.edit')
            ->middleware('menu.access:gestao_tvs');
        Route::put('devices/{device}', [\App\Http\Controllers\Admin\DeviceManagementController::class, 'update'])
            ->name('admin.devices.update')
            ->middleware('menu.access:gestao_tvs');

        Route::get('ativar-tv', [\App\Http\Controllers\Admin\DeviceActivationController::class, 'index'])
            ->name('admin.activate-tv.index')
            ->middleware('menu.access:ativar_tv');
        Route::post('activate-device', [\App\Http\Controllers\Admin\DeviceActivationController::class, 'activate'])
            ->name('admin.activate-device')
            ->middleware('menu.access:ativar_tv');
        Route::put('ativar-tv/devices/{device}', [\App\Http\Controllers\Admin\DeviceActivationController::class, 'updateDevice'])
            ->name('admin.activate-tv.devices.update')
            ->middleware('menu.access:ativar_tv');
        Route::delete('ativar-tv/devices/{device}', [\App\Http\Controllers\Admin\DeviceActivationController::class, 'destroyDevice'])
            ->name('admin.activate-tv.devices.destroy')
            ->middleware('menu.access:ativar_tv');

        Route::get('api-token', [\App\Http\Controllers\Admin\ApiTokenController::class, 'index'])
            ->name('admin.api-token.index')
            ->middleware('menu.access:token_api');
        Route::post('api-token/regenerate', [\App\Http\Controllers\Admin\ApiTokenController::class, 'regenerate'])
            ->name('admin.api-token.regenerate')
            ->middleware('menu.access:token_api');

        Route::resource('produtos', \App\Http\Controllers\Admin\ProdutoController::class, ['as' => 'admin'])
            ->scoped(['produto' => 'id'])
            ->only(['index', 'edit'])
            ->middleware('menu.access:produtos');
        Route::resource('empresas', \App\Http\Controllers\Admin\EmpresaController::class, ['as' => 'admin'])
            ->middleware('menu.access:empresas')
            ->only(['index', 'edit']);
        Route::post('empresas/{empresa}/selecionar', [\App\Http\Controllers\Admin\EmpresaController::class, 'selecionarEmpresaAtiva'])
            ->name('admin.empresas.selecionar')
            ->middleware('menu.access:empresas');
        Route::get('empresas/{empresa}/selecionar', [\App\Http\Controllers\Admin\EmpresaController::class, 'selecionarEmpresaAtiva'])
            ->name('admin.empresas.selecionar.get')
            ->middleware('menu.access:empresas');
        Route::post('empresas/limpar-selecao', [\App\Http\Controllers\Admin\EmpresaController::class, 'limparEmpresaAtiva'])
            ->name('admin.empresas.limpar-selecao')
            ->middleware('menu.access:empresas');
        Route::resource('departamentos', \App\Http\Controllers\Admin\DepartamentoController::class, ['as' => 'admin'])
            ->only(['index', 'edit'])
            ->middleware('menu.access:departamentos');
        Route::resource('grupos', \App\Http\Controllers\Admin\GrupoController::class, ['as' => 'admin'])
            ->only(['index', 'edit'])
            ->middleware('menu.access:grupos');

        Route::get('rede-social', [\App\Http\Controllers\Admin\SocialMediaTemplateController::class, 'index'])
            ->name('admin.social-media.index')
            ->middleware('menu.access:rede_social');
        Route::get('rede-social/whatsapp', [\App\Http\Controllers\Admin\SocialMediaTemplateController::class, 'index'])
            ->name('admin.social-media.whatsapp.index')
            ->middleware('menu.access:rede_social');
        Route::get('rede-social/instagram/connect', [\App\Http\Controllers\Admin\InstagramIntegrationController::class, 'redirect'])
            ->name('admin.social-media.instagram.connect')
            ->middleware('menu.access:rede_social');
        Route::get('rede-social/instagram/callback', [\App\Http\Controllers\Admin\InstagramIntegrationController::class, 'callback'])
            ->name('admin.social-media.instagram.callback')
            ->middleware('menu.access:rede_social');
        Route::post('rede-social/instagram/complete-selection', [\App\Http\Controllers\Admin\InstagramIntegrationController::class, 'completeSelection'])
            ->name('admin.social-media.instagram.complete-selection')
            ->middleware('menu.access:rede_social');
        Route::post('rede-social/instagram/disconnect', [\App\Http\Controllers\Admin\InstagramIntegrationController::class, 'disconnect'])
            ->name('admin.social-media.instagram.disconnect')
            ->middleware('menu.access:rede_social');

        Route::get('financeiro', [\App\Http\Controllers\Admin\FinanceiroController::class, 'index'])
            ->name('admin.financeiro.index')
            ->middleware('menu.access:financeiro');
        Route::get('financeiro/{empresa}', [\App\Http\Controllers\Admin\FinanceiroController::class, 'show'])
            ->name('admin.financeiro.show')
            ->middleware('menu.access:financeiro');
        Route::put('financeiro/{empresa}', [\App\Http\Controllers\Admin\FinanceiroController::class, 'update'])
            ->name('admin.financeiro.update')
            ->middleware('menu.access:financeiro');

        Route::resource('global-image-galleries', \App\Http\Controllers\Admin\GlobalImageGalleryController::class, ['as' => 'admin'])
            ->except(['show']);

        Route::get('galeria-imagem', [\App\Http\Controllers\Admin\GaleriaNovaController::class, 'index'])
            ->name('admin.galeria-imagem.index');
        Route::post('galeria-imagem', [\App\Http\Controllers\Admin\GaleriaNovaController::class, 'store'])
            ->name('admin.galeria-imagem.store');
        Route::delete('galeria-imagem/{galeriaNova}', [\App\Http\Controllers\Admin\GaleriaNovaController::class, 'destroy'])
            ->name('admin.galeria-imagem.destroy');

        Route::get('permissoes-usuarios', [\App\Http\Controllers\Admin\UserPermissionController::class, 'index'])
            ->name('admin.user-permissions.index');
        Route::get('permissoes-usuarios/{user}/edit', [\App\Http\Controllers\Admin\UserPermissionController::class, 'edit'])
            ->name('admin.user-permissions.edit');
        Route::put('permissoes-usuarios/{user}', [\App\Http\Controllers\Admin\UserPermissionController::class, 'update'])
            ->name('admin.user-permissions.update');
    });
    });
});

require __DIR__.'/auth.php';
