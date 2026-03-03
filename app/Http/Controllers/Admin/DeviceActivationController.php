<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use OpenApi\Attributes as OA;

class DeviceActivationController extends Controller
{
    #[OA\Get(
        path: '/admin/ativar-tv',
        tags: ['Admin TV'],
        summary: 'Exibe página de ativação de TV no painel admin',
        responses: [
            new OA\Response(response: 200, description: 'Página carregada'),
            new OA\Response(response: 403, description: 'Sem permissão')
        ]
    )]
    public function index(): View
    {
        $user = Auth::user();

        $empresas = $user->isDefaultAdmin()
            ? Empresa::query()->orderBy('NOME')->get(['id', 'NOME', 'CNPJ_CPF'])
            : collect();

        return view('admin.activate-tv', [
            'empresas' => $empresas,
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'empresaVinculada' => $user->empresa,
            'activatedToken' => session('activated_token'),
        ]);
    }

    #[OA\Post(
        path: '/admin/activate-device',
        tags: ['Admin TV'],
        summary: 'Processa ativação de TV por código',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/AdminActivateDevicePayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 302, description: 'Redireciona com sucesso/erro de validação'),
            new OA\Response(response: 403, description: 'Sem permissão')
        ]
    )]
    public function activate(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'code' => ['required', 'digits:5'],
            'empresa_id' => [
                $user->isDefaultAdmin() ? 'required' : 'nullable',
                'integer',
                'exists:empresa,id',
            ],
            'nome_tv' => ['required', 'string', 'max:120'],
            'local' => ['nullable', 'string', 'max:120'],
        ]);

        $empresa = $this->resolveEmpresa($validated['empresa_id'] ?? null, $user);

        $activation = DeviceActivation::query()
            ->where('code', $validated['code'])
            ->where('activated', false)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $activation) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['code' => 'Código inválido, expirado ou já utilizado.']);
        }

        $device = Device::query()->firstOrNew([
            'device_uuid' => $activation->device_uuid,
        ]);

        $device->empresa_id = $empresa->id;
        $device->nome = $validated['nome_tv'];
        $device->local = $validated['local'] ?? null;
        $device->token = $this->generateUniqueDeviceToken();
        $device->ativo = true;
        $device->last_seen_at = now();
        $device->save();

        $activation->activated = true;
        $activation->device_id = $device->id;
        $activation->save();

        return redirect()
            ->route('admin.activate-tv.index')
            ->with('success', 'TV ativada com sucesso.')
            ->with('activated_token', $device->token);
    }

    private function resolveEmpresa(?int $empresaId, $user): Empresa
    {
        if ($user->isDefaultAdmin()) {
            return Empresa::findOrFail($empresaId);
        }

        abort_unless($user->empresa_id, 403, 'Usuário sem empresa vinculada.');

        $empresa = Empresa::find($user->empresa_id);
        abort_unless($empresa, 404, 'Empresa vinculada não encontrada.');

        return $empresa;
    }

    private function generateUniqueDeviceToken(): string
    {
        do {
            $token = Str::random(60);
        } while (Device::query()->where('token', $token)->exists());

        return $token;
    }
}
