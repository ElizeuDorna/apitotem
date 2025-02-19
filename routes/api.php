<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/', function () {
    // return view('welcome');
 return "Olá Sejam Bem vindo a Api Totem ";
 });

Route::get('/produto', 'App\Http\Controllers\api\ProdutoController@index');
Route::post('/produto', 'App\Http\Controllers\api\ProdutoController@store');
Route::get('/produto/{id}', 'App\Http\Controllers\api\ProdutoController@show');
Route::put('/produto/{id}', 'App\Http\Controllers\api\ProdutoController@update');
Route::delete('/produto/{id}', 'App\Http\Controllers\api\ProdutoController@destroy');

Route::get('/departamento', 'App\Http\Controllers\api\DepartamentoController@index');
Route::post('/departamento', 'App\Http\Controllers\api\DepartamentoController@store');
Route::get('/departamento/{id}', 'App\Http\Controllers\api\DepartamentoController@show');
Route::put('/departamento/{id}', 'App\Http\Controllers\api\DepartamentoController@update');
Route::delete('/departamento/{id}', 'App\Http\Controllers\api\DepartamentoController@destroy');
