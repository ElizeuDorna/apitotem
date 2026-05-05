<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceActivation;
use App\Models\DeviceConfiguration;
use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\User;
use App\Models\WebScreenModel;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
        $empresaVinculada = $user->empresa;
        $isRevenda = $empresaVinculada?->isRevenda() ?? false;
        $empresaIdAtiva = EmpresaContext::resolveEmpresaIdForUser($user);
        $canShowAllDevices = $user->isDefaultAdmin() || $isRevenda;
        $showAllDevices = $canShowAllDevices && $this->requestWantsAllDevices();

        $empresaAtiva = $empresaIdAtiva
            ? Empresa::query()->find($empresaIdAtiva, ['id', 'NOME', 'nome', 'CNPJ_CPF', 'cnpj_cpf'])
            : null;

        $devicesQuery = Device::query()
            ->with(['empresa', 'configuration'])
            ->orderByDesc('id');

        if ($user->isDefaultAdmin()) {
            if ($showAllDevices) {
                // Admin principal pode optar por ver todos os dispositivos, independente da empresa ativa.
            } elseif ($empresaIdAtiva) {
                $devicesQuery->where('empresa_id', $empresaIdAtiva);
            } else {
                $devicesQuery->whereRaw('1 = 0');
            }
        } elseif ($isRevenda) {
            if ($showAllDevices && $empresaVinculada) {
                $devicesQuery->whereHas('empresa', function ($query) use ($empresaVinculada) {
                    $query->where('revenda_id', $empresaVinculada->id);
                });
            } else {
                $devicesQuery->where('empresa_id', EmpresaContext::requireEmpresaId($user));
            }
        } else {
            $devicesQuery->where('empresa_id', EmpresaContext::requireEmpresaId($user));
        }

        $devices = $devicesQuery
            ->paginate(15, ['*'], 'devices_page')
            ->appends(['show_all_devices' => $showAllDevices ? '1' : '0']);

        $visibleEmpresaIds = $devices->getCollection()
            ->pluck('empresa_id')
            ->filter()
            ->map(fn ($empresaId) => (int) $empresaId)
            ->unique()
            ->values()
            ->all();

        $modelsByEmpresa = WebScreenModel::query()
            ->whereIn('empresa_id', $visibleEmpresaIds)
            ->where('is_admin_default', false)
            ->orderBy('nome')
            ->get(['id', 'empresa_id', 'nome'])
            ->groupBy('empresa_id');

        $departmentsByEmpresa = Departamento::query()
            ->whereIn('empresa_id', $visibleEmpresaIds)
            ->orderBy('nome')
            ->get(['id', 'empresa_id', 'nome'])
            ->groupBy('empresa_id');

        $groupsByEmpresa = Grupo::query()
            ->whereIn('empresa_id', $visibleEmpresaIds)
            ->orderBy('nome')
            ->get(['id', 'empresa_id', 'departamento_id', 'nome'])
            ->groupBy('empresa_id');

        $activationEmpresa = (! $user->isDefaultAdmin() || $empresaIdAtiva)
            ? $this->resolveEmpresa($user)
            : null;

        $adminDefaultModels = $user->isDefaultAdmin()
            ? WebScreenModel::query()
                ->where('is_admin_default', true)
                ->orderBy('nome')
                ->get(['id', 'nome', 'is_admin_default'])
            : collect();

        $activationModels = collect();
        if ($activationEmpresa) {
            $activationModels = WebScreenModel::query()
                ->where('empresa_id', $activationEmpresa->id)
                ->where('is_admin_default', false)
                ->orderBy('nome')
                ->get(['id', 'nome', 'is_admin_default']);

            if ($user->isDefaultAdmin()) {
                $activationModels = $activationModels
                    ->concat($adminDefaultModels)
                    ->unique('id')
                    ->values();
            }
        }

        return view('admin.activate-tv', [
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'canShowAllDevices' => $canShowAllDevices,
            'isRevenda' => $isRevenda,
            'adminSemEmpresaAtiva' => $user->isDefaultAdmin() && ! $empresaIdAtiva,
            'showAllDevices' => $showAllDevices,
            'empresaAtiva' => $empresaAtiva,
            'empresaVinculada' => $empresaVinculada,
            'activatedToken' => session('activated_token'),
            'activationModels' => $activationModels,
            'adminDefaultModels' => $adminDefaultModels,
            'activationDepartments' => $activationEmpresa
                ? Departamento::query()
                    ->where('empresa_id', $activationEmpresa->id)
                    ->orderBy('nome')
                    ->get(['id', 'nome'])
                : collect(),
            'activationGroups' => $activationEmpresa
                ? Grupo::query()
                    ->where('empresa_id', $activationEmpresa->id)
                    ->orderBy('nome')
                    ->get(['id', 'departamento_id', 'nome'])
                : collect(),
            'modelsByEmpresa' => $modelsByEmpresa,
            'departmentsByEmpresa' => $departmentsByEmpresa,
            'groupsByEmpresa' => $groupsByEmpresa,
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
            'nome_tv' => ['required', 'string', 'max:120'],
            'local' => ['nullable', 'string', 'max:120'],
            'web_screen_model_id' => ['nullable', 'integer'],
            'product_department_id' => ['nullable', 'integer'],
            'product_group_id' => ['nullable', 'integer'],
        ]);

        $empresa = $this->resolveEmpresa($user);
        $webScreenModelId = $this->resolveModelIdForEmpresa((int) $empresa->id, $validated['web_screen_model_id'] ?? null, $user);
        $productDepartmentId = $this->resolveDepartmentIdForEmpresa((int) $empresa->id, $validated['product_department_id'] ?? null);
        $productGroupId = $this->resolveGroupIdForEmpresa((int) $empresa->id, $validated['product_group_id'] ?? null, $productDepartmentId);
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

        $existingConfiguration = $device->configuration;

        DeviceConfiguration::query()->updateOrCreate(
            ['device_id' => $device->id],
            [
                'web_screen_model_id' => $webScreenModelId,
                'product_department_id' => $productDepartmentId,
                'product_group_id' => $productGroupId,
                'web_config_payload' => $existingConfiguration?->web_config_payload,
                'atualizar_produtos_segundos' => $existingConfiguration?->atualizar_produtos_segundos ?? 30,
                'volume' => $existingConfiguration?->volume ?? 50,
                'orientacao' => $existingConfiguration?->orientacao ?? 'landscape',
            ]
        );

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
            'web_screen_model_id' => ['nullable', 'integer'],
            'product_department_id' => ['nullable', 'integer'],
            'product_group_id' => ['nullable', 'integer'],
        ]);

        $empresaId = EmpresaContext::requireEmpresaId($user);
        $webScreenModelId = $this->resolveModelIdForEmpresa((int) $device->empresa_id, $validated['web_screen_model_id'] ?? null, $user);
        $productDepartmentId = $this->resolveDepartmentIdForEmpresa((int) $device->empresa_id, $validated['product_department_id'] ?? null);
        $productGroupId = $this->resolveGroupIdForEmpresa((int) $device->empresa_id, $validated['product_group_id'] ?? null, $productDepartmentId);

        $device->update([
            'nome' => $validated['nome'],
            'local' => $validated['local'] ?? null,
            'ativo' => (bool) ($validated['ativo'] ?? false),
            'empresa_id' => $empresaId,
        ]);

        $existingConfiguration = $device->configuration;

        DeviceConfiguration::query()->updateOrCreate(
            ['device_id' => $device->id],
            [
                'web_screen_model_id' => $webScreenModelId,
                'product_department_id' => $productDepartmentId,
                'product_group_id' => $productGroupId,
                'web_config_payload' => $existingConfiguration?->web_config_payload,
                'atualizar_produtos_segundos' => $existingConfiguration?->atualizar_produtos_segundos ?? 30,
                'volume' => $existingConfiguration?->volume ?? 50,
                'orientacao' => $existingConfiguration?->orientacao ?? 'landscape',
            ]
        );

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

    private function resolveEmpresa($user): Empresa
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

    private function resolveModelIdForEmpresa(int $empresaId, mixed $modelId, User $user): ?int
    {
        $normalizedModelId = (int) $modelId;
        if ($normalizedModelId <= 0) {
            return null;
        }

        $query = WebScreenModel::query()
            ->where('empresa_id', $empresaId)
            ->where('id', $normalizedModelId);

        if ($user->isDefaultAdmin()) {
            $query->orWhere(function ($or) use ($normalizedModelId) {
                $or->where('id', $normalizedModelId)
                    ->where('is_admin_default', true);
            });
        }

        $exists = $query->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'web_screen_model_id' => 'Modelo inválido para a empresa selecionada.',
            ]);
        }

        return $normalizedModelId;
    }

    private function resolveDepartmentIdForEmpresa(int $empresaId, mixed $departmentId): ?int
    {
        $normalizedDepartmentId = (int) $departmentId;
        if ($normalizedDepartmentId <= 0) {
            return null;
        }

        $exists = Departamento::query()
            ->where('empresa_id', $empresaId)
            ->where('id', $normalizedDepartmentId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'product_department_id' => 'Departamento inválido para a empresa selecionada.',
            ]);
        }

        return $normalizedDepartmentId;
    }

    private function resolveGroupIdForEmpresa(int $empresaId, mixed $groupId, ?int $departmentId = null): ?int
    {
        $normalizedGroupId = (int) $groupId;
        if ($normalizedGroupId <= 0) {
            return null;
        }

        $groupQuery = Grupo::query()
            ->where('empresa_id', $empresaId)
            ->where('id', $normalizedGroupId);

        if ($departmentId) {
            $groupQuery->where('departamento_id', $departmentId);
        }

        $group = $groupQuery->first(['id']);

        if (! $group) {
            throw ValidationException::withMessages([
                'product_group_id' => $departmentId
                    ? 'Grupo inválido para o departamento selecionado.'
                    : 'Grupo inválido para a empresa selecionada.',
            ]);
        }

        return (int) $group->id;
    }
}
