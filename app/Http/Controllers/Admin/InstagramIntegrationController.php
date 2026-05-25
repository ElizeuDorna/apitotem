<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InstagramGraphService;
use App\Services\SocialMediaTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class InstagramIntegrationController extends Controller
{
    private const STATE_SESSION_KEY = 'social-media.instagram.state';

    private const PENDING_SELECTION_SESSION_KEY = 'social-media.instagram.pending-selection';

    public function redirect(SocialMediaTemplateService $templateService, InstagramGraphService $instagramService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $templateService->activeEmpresaForUser($user);

        if (! $instagramService->isConfigured()) {
            return redirect()
                ->route('admin.social-media.index')
                ->with('error', 'Configure META_APP_ID, META_APP_SECRET e META_REDIRECT_URI antes de conectar o Instagram.');
        }

        session()->forget(self::PENDING_SELECTION_SESSION_KEY);

        $state = Str::random(40);
        session([self::STATE_SESSION_KEY => $state]);

        return redirect()->away($instagramService->authorizationUrl($state));
    }

    public function callback(Request $request, SocialMediaTemplateService $templateService, InstagramGraphService $instagramService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresa = $templateService->activeEmpresaForUser($user);

        $expectedState = (string) session()->pull(self::STATE_SESSION_KEY, '');
        $receivedState = (string) $request->query('state', '');

        $oauthError = trim((string) $request->query('error', ''));
        $oauthDescription = trim((string) $request->query('error_description', ''));

        if ($oauthError !== '') {
            $message = $oauthDescription !== ''
                ? 'Meta retornou erro na autorizacao: '.$oauthDescription
                : 'Meta retornou erro na autorizacao: '.$oauthError;

            return redirect()
                ->route('admin.social-media.index')
                ->with('error', $message);
        }

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()
                ->route('admin.social-media.index')
                ->with('error', 'Falha na validacao do retorno do Instagram. Tente conectar novamente.');
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return redirect()
                ->route('admin.social-media.index')
                ->with('error', 'O Instagram nao retornou um codigo de autorizacao valido.');
        }

        try {
            $connectionData = $instagramService->beginConnectEmpresa($code);

            if (count($connectionData['accounts']) > 1) {
                session([
                    self::PENDING_SELECTION_SESSION_KEY => [
                        'empresa_id' => $empresa->id,
                        'expires_in' => $connectionData['expires_in'],
                        'accounts' => $connectionData['accounts'],
                    ],
                ]);

                return redirect()
                    ->route('admin.social-media.index')
                    ->with('success', 'Selecione a pagina do Facebook e a conta Instagram corretas para concluir a conexao.');
            }

            $instagramService->connectEmpresaWithSelection($empresa, $connectionData['accounts'][0], $connectionData['expires_in']);
            session()->forget(self::PENDING_SELECTION_SESSION_KEY);

            return redirect()
                ->route('admin.social-media.index')
                ->with('success', 'Instagram conectado com sucesso.');
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.social-media.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function completeSelection(Request $request, SocialMediaTemplateService $templateService, InstagramGraphService $instagramService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresa = $templateService->activeEmpresaForUser($user);
        $pendingSelection = session(self::PENDING_SELECTION_SESSION_KEY, []);

        if (($pendingSelection['empresa_id'] ?? null) !== $empresa->id) {
            session()->forget(self::PENDING_SELECTION_SESSION_KEY);

            return redirect()
                ->route('admin.social-media.index')
                ->with('error', 'A selecao pendente da Meta nao corresponde mais a empresa ativa. Inicie a conexao novamente.');
        }

        $validated = $request->validate([
            'facebook_page_id' => ['required', 'string'],
        ]);

        $selectedAccount = collect($pendingSelection['accounts'] ?? [])->firstWhere('facebook_page_id', (string) $validated['facebook_page_id']);

        if (! $selectedAccount) {
            return redirect()
                ->route('admin.social-media.index')
                ->with('error', 'Selecione uma pagina valida para concluir a conexao Meta.');
        }

        $instagramService->connectEmpresaWithSelection(
            empresa: $empresa,
            selectedAccount: $selectedAccount,
            expiresIn: (int) ($pendingSelection['expires_in'] ?? 0),
        );

        session()->forget(self::PENDING_SELECTION_SESSION_KEY);

        return redirect()
            ->route('admin.social-media.index')
            ->with('success', 'Meta conectada com sucesso para a pagina selecionada.');
    }

    public function disconnect(SocialMediaTemplateService $templateService, InstagramGraphService $instagramService): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $empresa = $templateService->activeEmpresaForUser($user);
        $instagramService->disconnectEmpresa($empresa);
        session()->forget(self::PENDING_SELECTION_SESSION_KEY);

        return redirect()
            ->route('admin.social-media.index')
            ->with('success', 'Integracao com Instagram desconectada.');
    }
}