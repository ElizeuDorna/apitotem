<?php

namespace App\Http\Middleware\Api;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token do dispositivo não informado.',
            ], 401);
        }

        $device = Device::query()
            ->with('empresa')
            ->where('token', $token)
            ->where('ativo', true)
            ->first();

        if (! $device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token do dispositivo inválido.',
            ], 401);
        }

        $request->attributes->set('device', $device);
        $request->attributes->set('empresa', $device->empresa);

        return $next($request);
    }
}
