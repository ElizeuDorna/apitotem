<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppEmbeddedSignupService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class WhatsAppEmbeddedSignupController extends Controller
{
    public function show(WhatsAppService $whatsAppService, WhatsAppEmbeddedSignupService $embeddedSignupService): View
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresa = $whatsAppService->empresaForUser($user);

        return view('admin.whatsapp-embedded-signup', [
            'empresa' => $empresa,
            'integration' => $whatsAppService->integrationForUser($user),
            'embeddedSignup' => [
                'enabled' => $embeddedSignupService->isConfigured(),
                'appId' => $embeddedSignupService->appId(),
                'configurationId' => $embeddedSignupService->configurationId(),
                'graphVersion' => $embeddedSignupService->graphVersion(),
                'onboardUrl' => route('admin.social-media.whatsapp.embedded-signup.onboard'),
                'redirectUrl' => route('admin.social-media.whatsapp.index'),
                'csrfToken' => csrf_token(),
            ],
        ]);
    }

    public function onboard(Request $request, WhatsAppEmbeddedSignupService $embeddedSignupService, WhatsAppService $whatsAppService): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $whatsAppService->empresaForUser($user);

        try {
            $integration = $embeddedSignupService->onboardForUser($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp conectado com sucesso pela Meta.',
                'integration' => [
                    'status' => $integration->status,
                    'meta_phone_number_id' => $integration->meta_phone_number_id,
                    'display_phone_number' => $integration->display_phone_number,
                ],
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}