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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin CRUDs - protegidas por autenticação
    Route::get('/admin/configuracao', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'edit'])
        ->middleware('menu.access:configuracao');
    Route::post('/admin/configuracao', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'update'])
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

        Route::get('api-token', [\App\Http\Controllers\Admin\ApiTokenController::class, 'index'])
            ->name('admin.api-token.index')
            ->middleware('menu.access:token_api');
        Route::post('api-token/regenerate', [\App\Http\Controllers\Admin\ApiTokenController::class, 'regenerate'])
            ->name('admin.api-token.regenerate')
            ->middleware('menu.access:token_api');

        Route::resource('produtos', \App\Http\Controllers\Admin\ProdutoController::class, ['as' => 'admin'])
            ->middleware('menu.access:produtos');
        Route::resource('empresas', \App\Http\Controllers\Admin\EmpresaController::class, ['as' => 'admin'])
            ->middleware('menu.access:empresas')
            ->except(['show']);
        Route::resource('departamentos', \App\Http\Controllers\Admin\DepartamentoController::class, ['as' => 'admin'])
            ->middleware('menu.access:departamentos');
        Route::resource('grupos', \App\Http\Controllers\Admin\GrupoController::class, ['as' => 'admin'])
            ->middleware('menu.access:grupos');

        Route::get('permissoes-usuarios', [\App\Http\Controllers\Admin\UserPermissionController::class, 'index'])
            ->name('admin.user-permissions.index');
        Route::get('permissoes-usuarios/{user}/edit', [\App\Http\Controllers\Admin\UserPermissionController::class, 'edit'])
            ->name('admin.user-permissions.edit');
        Route::put('permissoes-usuarios/{user}', [\App\Http\Controllers\Admin\UserPermissionController::class, 'update'])
            ->name('admin.user-permissions.update');
    });
});

require __DIR__.'/auth.php';
