<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppGraphService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request, WhatsAppGraphService $graphService): Response
    {
        $challenge = $graphService->verifyWebhook(
            (string) config('services.whatsapp_graph.verify_token', ''),
            $request->query('hub_mode'),
            $request->query('hub_verify_token'),
            $request->query('hub_challenge')
        );

        abort_unless($challenge !== null, 403);

        return response($challenge, 200);
    }

    public function receive(Request $request, WhatsAppService $whatsAppService): Response
    {
        $whatsAppService->processWebhookPayload($request->all());

        return response('EVENT_RECEIVED', 200);
    }
}
