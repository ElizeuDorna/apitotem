<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $devicesQuery = Device::query()
            ->with('empresa')
            ->where('ativo', true)
            ->orderByDesc('id');

        if (! $user->isDefaultAdmin()) {
            $devicesQuery->where('empresa_id', $user->empresa_id);
        }

        $devices = $devicesQuery->paginate(15, ['*'], 'devices_page');

        return view('admin.activate-tv', [
            'empresas' => $empresas,
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'empresaVinculada' => $user->empresa,
            'activatedToken' => session('activated_token'),
            'devices' => $devices,
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
            'code' => ['required', 'alpha_num', 'size:10'],
            'empresa_id' => [
                $user->isDefaultAdmin() ? 'required' : 'nullable',
                'integer',
                'exists:empresa,id',
            ],
            'nome_tv' => ['required', 'string', 'max:120'],
            'local' => ['nullable', 'string', 'max:120'],
        ]);

        $empresa = $this->resolveEmpresa($validated['empresa_id'] ?? null, $user);
        $activationCode = strtoupper((string) $validated['code']);

        $activation = DeviceActivation::query()
            ->where('code', $activationCode)
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

    public function updateDevice(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeDeviceAccess($device);

        $user = Auth::user();

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'local' => ['nullable', 'string', 'max:120'],
            'ativo' => ['nullable', 'boolean'],
            'empresa_id' => [
                $user->isDefaultAdmin() ? 'required' : 'nullable',
                'integer',
                'exists:empresa,id',
            ],
        ]);

        $empresaId = $user->isDefaultAdmin()
            ? (int) $validated['empresa_id']
            : (int) $user->empresa_id;

        $device->update([
            'nome' => $validated['nome'],
            'local' => $validated['local'] ?? null,
            'ativo' => (bool) ($validated['ativo'] ?? false),
            'empresa_id' => $empresaId,
        ]);

        return redirect()
            ->route('admin.activate-tv.index', ['devices_page' => $request->input('devices_page')])
            ->with('success', 'TV atualizada com sucesso.');
    }

    public function destroyDevice(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeDeviceAccess($device);

        $device->delete();

        return redirect()
            ->route('admin.activate-tv.index', ['devices_page' => $request->input('devices_page')])
            ->with('success', 'TV removida com sucesso.');
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

    private function authorizeDeviceAccess(Device $device): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        abort_unless((int) $user->empresa_id === (int) $device->empresa_id, 403);
    }

    private function generateUniqueDeviceToken(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $token = '';

            for ($index = 0; $index < 16; $index++) {
                $token .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (Device::query()->where('token', $token)->exists());

        return $token;
    }
}
