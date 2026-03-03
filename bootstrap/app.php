<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException; // adcionando
use Illuminate\Http\Request;  // adcionando

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'menu.access' => \App\Http\Middleware\EnsureMenuAccess::class,
            'identify.company' => \App\Http\Middleware\Api\IdentifyCompany::class,
            'device.auth' => \App\Http\Middleware\Api\DeviceAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            // Se a requisição foi feita para uma API (espera JSON)
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Não autenticado.'], 401);
            }

            // Para requisições web, ainda pode tentar redirecionar para 'login'
            // (Você precisaria de uma rota nomeada 'login' em routes/web.php para isso funcionar em apps web)
            // return redirect()->guest(route('login'));
        });
    })->create();
