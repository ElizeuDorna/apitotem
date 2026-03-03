<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\ProdutoController;
use App\Http\Controllers\api\DepartamentoController;
use App\Http\Controllers\api\GrupoController;
use App\Http\Controllers\api\ConfiguracaoController;
use App\Http\Controllers\api\EmpresaController;
use App\Http\Controllers\api\TvController;

Route::get('/', function () {
    return response()->json([
        'mensagem' => 'Bem-vindo à API Totem',
        'versao' => '1.0.0',
        'endpoints' => [
            'POST /api/login' => 'Autenticar empresa e gerar token',
            'GET /api/produtos' => 'Listar produtos da empresa autenticada',
            'GET /api/produtos/{CODIGO}' => 'Obter um produto específico',
            'POST /api/produtos' => 'Cadastrar novo produto',
            'PUT /api/produtos/{CODIGO}' => 'Atualizar um produto',
            'DELETE /api/produtos/{CODIGO}' => 'Deletar um produto',
            'GET /api/departamentos' => 'Listar departamentos da empresa autenticada',
            'GET /api/grupos' => 'Listar grupos da empresa autenticada',
            'GET /api/configuracoes' => 'Obter configuração da empresa autenticada',
            'POST /api/configuracoes' => 'Salvar configuração da empresa autenticada',
            'PUT /api/configuracoes' => 'Atualizar configuração da empresa autenticada',
            'GET /api/empresas' => 'Listar todas as empresas',
            'POST /api/empresas' => 'Cadastrar nova empresa',
            'PUT /api/empresas/{id}' => 'Atualizar uma empresa',
            'DELETE /api/empresas/{id}' => 'Deletar uma empresa',
            'POST /api/tv/activation-code' => 'Gerar código de ativação para TV',
            'POST /api/tv/check-activation' => 'Consultar status de ativação da TV',
            'POST /api/tv/heartbeat' => 'Atualizar atividade do dispositivo',
            'GET /api/tv/bootstrap' => 'Carregar configuração/template da TV',
            'GET /api/tv/produtos' => 'Listar produtos por token de dispositivo',
            'GET /api/tv/midias' => 'Listar mídias do template da TV',
            'GET /api/tv/ofertas' => 'Listar ofertas por token de dispositivo',
        ]
    ]);
});

Route::post('login', [AuthController::class, 'login']);
Route::post('tv/activation-code', [TvController::class, 'activationCode']);
Route::post('tv/check-activation', [TvController::class, 'checkActivation']);
Route::post('tv/heartbeat', [TvController::class, 'heartbeat']);
Route::get('tv/produtos', [TvController::class, 'produtos']);

Route::middleware('device.auth')->prefix('tv')->group(function () {
    Route::get('bootstrap', [TvController::class, 'bootstrap']);
    Route::get('ofertas', [TvController::class, 'ofertas']);
    Route::get('midias', [TvController::class, 'midias']);
});

// Rotas de Empresa
Route::apiResource('empresas', EmpresaController::class)->except(['show']);

Route::middleware('identify.company')->group(function () {
    Route::apiResource('produtos', ProdutoController::class);
    Route::apiResource('departamentos', DepartamentoController::class);
    Route::apiResource('grupos', GrupoController::class);

    Route::get('configuracoes', [ConfiguracaoController::class, 'index']);
    Route::post('configuracoes', [ConfiguracaoController::class, 'store']);
    Route::put('configuracoes', [ConfiguracaoController::class, 'update']);
    Route::delete('configuracoes', [ConfiguracaoController::class, 'destroy']);

    Route::get('configuracao', [ConfiguracaoController::class, 'index']);
    Route::post('configuracao', [ConfiguracaoController::class, 'store']);
    Route::put('configuracao', [ConfiguracaoController::class, 'update']);
    Route::delete('configuracao', [ConfiguracaoController::class, 'destroy']);
});







