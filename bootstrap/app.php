<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException; // adcionando
use Illuminate\Http\Request;  // adcionando
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = env('TRUSTED_PROXIES');

        if (! is_null($trustedProxies) && $trustedProxies !== '') {
            $middleware->trustProxies(
                at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', (string) $trustedProxies)),
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_PREFIX
                    | Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }

        $middleware->alias([
            'menu.access' => \App\Http\Middleware\EnsureMenuAccess::class,
            'revenda.empresa.selecionada' => \App\Http\Middleware\EnsureRevendaEmpresaSelecionada::class,
            'identify.company' => \App\Http\Middleware\Api\IdentifyCompany::class,
            'device.auth' => \App\Http\Middleware\Api\DeviceAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sessão expirada.'], 419);
            }

            return redirect()->route('login')
                ->with('status', 'Sua sessão expirou. Faça login novamente.');
        });

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
