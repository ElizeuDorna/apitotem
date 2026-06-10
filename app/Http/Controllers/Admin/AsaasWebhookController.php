<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AsaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AsaasWebhookController extends Controller
{
    public function receive(Request $request, AsaasService $asaas): JsonResponse
    {
        if ((string) $request->header('asaas-access-token', '') === '' && $asaas->webhookToken() === null) {
            abort(503, 'Webhook do Asaas nao configurado.');
        }

        try {
            $asaas->handleWebhook($request->all(), (string) $request->header('asaas-access-token', ''));
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();

            abort_if($message === 'Webhook do Asaas nao configurado para esta conta.', 503, $message);
            abort_if($message === 'Token de webhook invalido.', 403, $message);

            throw $exception;
        }

        return response()->json(['ok' => true]);
    }
}