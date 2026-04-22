<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\Empresa;
use App\Support\EmpresaContext;
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
        $empresaIdAtiva = EmpresaContext::resolveEmpresaIdForUser($user);
        $showAllDevices = $user->isDefaultAdmin() && $this->requestWantsAllDevices();

        $empresaAtiva = $empresaIdAtiva
            ? Empresa::query()->find($empresaIdAtiva, ['id', 'NOME', 'nome', 'CNPJ_CPF', 'cnpj_cpf'])
            : null;

        $devicesQuery = Device::query()
            ->with('empresa')
            ->orderByDesc('id');

        if ($user->isDefaultAdmin()) {
            if ($showAllDevices) {
                // Admin principal pode optar por ver todos os dispositivos, independente da empresa ativa.
            } elseif ($empresaIdAtiva) {
                $devicesQuery->where('empresa_id', $empresaIdAtiva);
            } else {
                $devicesQuery->whereRaw('1 = 0');
            }
        } else {
            $devicesQuery->where('empresa_id', EmpresaContext::requireEmpresaId($user));
        }

        $devices = $devicesQuery
            ->paginate(15, ['*'], 'devices_page')
            ->appends(['show_all_devices' => $showAllDevices ? '1' : '0']);

        return view('admin.activate-tv', [
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'adminSemEmpresaAtiva' => $user->isDefaultAdmin() && ! $empresaIdAtiva,
            'showAllDevices' => $showAllDevices,
            'empresaAtiva' => $empresaAtiva,
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
                'nullable',
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
            $latestActivation = DeviceActivation::query()
                ->with(['device.empresa'])
                ->where('code', $activationCode)
                ->latest('id')
                ->first();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['code' => $this->buildActivationCodeErrorMessage($latestActivation)]);
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
            ->with('activated_token', $device->token)
            ->with('activated_device_uuid', $device->device_uuid);
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
                'nullable',
                'integer',
                'exists:empresa,id',
            ],
        ]);

        $empresaId = EmpresaContext::requireEmpresaId($user);

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
            return Empresa::findOrFail(EmpresaContext::requireEmpresaId($user));
        }

        $empresa = Empresa::find(EmpresaContext::requireEmpresaId($user));
        abort_unless($empresa, 404, 'Empresa vinculada não encontrada.');

        return $empresa;
    }

    private function authorizeDeviceAccess(Device $device): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            $empresaIdAtiva = EmpresaContext::requireEmpresaId($user);
            abort_unless((int) $empresaIdAtiva === (int) $device->empresa_id, 403);
            return;
        }

        abort_unless((int) EmpresaContext::requireEmpresaId($user) === (int) $device->empresa_id, 403);
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

    private function requestWantsAllDevices(): bool
    {
        $value = request()->query('show_all_devices', '0');

        return in_array((string) $value, ['1', 'true', 'on'], true);
    }

    private function buildActivationCodeErrorMessage(?DeviceActivation $activation): string
    {
        if (! $activation) {
            return 'Código inválido. Verifique o código mostrado na TV e tente novamente.';
        }

        if ($activation->activated) {
            $device = $activation->device;

            if (! $device) {
                return 'Este código já foi utilizado anteriormente, mas o dispositivo vinculado não foi localizado na base.';
            }

            $empresaNome = $device->empresa?->NOME ?? $device->empresa?->nome ?? 'Empresa não identificada';
            $deviceNome = $device->nome ?: 'TV sem nome';
            $deviceUuid = $device->device_uuid ?: $activation->device_uuid;

            return sprintf(
                'Este código já foi utilizado pela TV "%s" da empresa "%s". Identificação do dispositivo: %s. Verifique em TVs cadastradas ou atualize o cadastro existente.',
                $deviceNome,
                $empresaNome,
                $deviceUuid ?: 'não disponível'
            );
        }

        if ($activation->expires_at && $activation->expires_at->isPast()) {
            return sprintf(
                'Este código expirou em %s e precisa ser gerado novamente na TV.',
                $activation->expires_at->format('d/m/Y H:i:s')
            );
        }

        return 'Código inválido, expirado ou já utilizado.';
    }
}
