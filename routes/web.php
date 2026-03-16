<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sobre', function () {
    return view('sobre');
});

Route::get('/contato', function () {
    return view('contato');
});

Route::view('/tv/totemweb', 'tv.produtos')->name('tv.totemweb');
Route::view('/tv/totemweb/configuracao', 'tv.configuracao')->name('tv.totemweb.configuracao');
Route::redirect('/tv/telaweb01', '/tv/totemweb');
Route::redirect('/tv/telaweb01/configuracao', '/tv/totemweb/configuracao');
Route::redirect('/tv/produtos', '/tv/totemweb');
Route::redirect('/tv/configuracao', '/tv/totemweb/configuracao');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/admin/revenda/empresas', [\App\Http\Controllers\Admin\RevendaEmpresaContextController::class, 'index'])
        ->name('admin.revenda.empresas.index')
        ->middleware('menu.access:empresas');
    Route::post('/admin/revenda/empresas/{empresa}/acessar', [\App\Http\Controllers\Admin\RevendaEmpresaContextController::class, 'acessar'])
        ->name('admin.revenda.empresas.acessar')
        ->middleware('menu.access:empresas');

    Route::middleware('revenda.empresa.selecionada')->group(function () {

    // Admin CRUDs - protegidas por autenticação
    Route::get('/admin/configuracao', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'edit'])
        ->middleware('menu.access:configuracao');
    Route::post('/admin/configuracao', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'update'])
        ->middleware('menu.access:configuracao');
        Route::get('/admin/configuracao-tela-web', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'edit'])
            ->name('admin.web-screen-config.edit')
            ->middleware('menu.access:configuracao');
        Route::post('/admin/configuracao-tela-web', [\App\Http\Controllers\Admin\WebScreenConfigController::class, 'update'])
            ->name('admin.web-screen-config.update')
            ->middleware('menu.access:configuracao');
        Route::get('/admin/global-image-galleries/lookup/{code}', [\App\Http\Controllers\Admin\GlobalImageGalleryController::class, 'lookupByCode'])
            ->name('admin.global-image-galleries.lookup')
            ->middleware('menu.access:configuracao');
        Route::get('/admin/global-image-galleries/search-by-name', [\App\Http\Controllers\Admin\GlobalImageGalleryController::class, 'searchByName'])
            ->name('admin.global-image-galleries.search-by-name')
            ->middleware('menu.access:configuracao');
        Route::get('/admin/organizar-lista', [\App\Http\Controllers\Admin\OrganizarListaController::class, 'edit'])
            ->name('admin.organizar-lista.edit')
            ->middleware('menu.access:configuracao');
        Route::post('/admin/organizar-lista', [\App\Http\Controllers\Admin\OrganizarListaController::class, 'update'])
            ->name('admin.organizar-lista.update')
            ->middleware('menu.access:configuracao');

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

        Route::resource('templates', \App\Http\Controllers\Admin\TemplateController::class, ['as' => 'admin'])
            ->middleware('menu.access:editor_template')
            ->except(['show']);
        Route::post('templates/{template}/items', [\App\Http\Controllers\Admin\TemplateController::class, 'addItem'])
            ->name('admin.templates.items.store')
            ->middleware('menu.access:editor_template');
        Route::delete('templates/{template}/items/{item}', [\App\Http\Controllers\Admin\TemplateController::class, 'deleteItem'])
            ->name('admin.templates.items.destroy')
            ->middleware('menu.access:editor_template');

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
            ->middleware('menu.access:produtos');
        Route::resource('empresas', \App\Http\Controllers\Admin\EmpresaController::class, ['as' => 'admin'])
            ->middleware('menu.access:empresas')
            ->except(['show']);
        Route::resource('departamentos', \App\Http\Controllers\Admin\DepartamentoController::class, ['as' => 'admin'])
            ->middleware('menu.access:departamentos');
        Route::resource('grupos', \App\Http\Controllers\Admin\GrupoController::class, ['as' => 'admin'])
            ->middleware('menu.access:grupos');

        Route::resource('global-image-galleries', \App\Http\Controllers\Admin\GlobalImageGalleryController::class, ['as' => 'admin'])
            ->except(['show']);

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
