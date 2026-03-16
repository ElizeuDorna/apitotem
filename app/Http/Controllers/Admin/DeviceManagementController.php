<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceConfiguration;
use App\Models\Template;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeviceManagementController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $query = Device::query()->with(['empresa', 'configuration.template'])->orderByDesc('id');

        if (! $user->isDefaultAdmin()) {
            $query->where('empresa_id', EmpresaContext::requireEmpresaId($user));
        }

        $devices = $query->paginate(20);

        return view('admin.devices.index', [
            'devices' => $devices,
        ]);
    }

    public function edit(Device $device): View
    {
        $this->authorizeDeviceAccess($device);

        $configuration = DeviceConfiguration::query()->firstOrCreate(
            ['device_id' => $device->id],
            [
                'atualizar_produtos_segundos' => 30,
                'volume' => 50,
                'orientacao' => 'landscape',
            ]
        );

        $templates = Template::query()
            ->where('empresa_id', $device->empresa_id)
            ->orderBy('nome')
            ->get();

        return view('admin.devices.edit', [
            'device' => $device,
            'configuration' => $configuration,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $this->authorizeDeviceAccess($device);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'local' => ['nullable', 'string', 'max:120'],
            'ativo' => ['nullable', 'boolean'],
            'template_id' => ['nullable', 'integer'],
            'atualizar_produtos_segundos' => ['required', 'integer', 'min:5', 'max:3600'],
            'volume' => ['required', 'integer', 'min:0', 'max:100'],
            'orientacao' => ['required', 'in:landscape,portrait'],
        ]);

        $device->update([
            'nome' => $validated['nome'],
            'local' => $validated['local'] ?? null,
            'ativo' => (bool) ($validated['ativo'] ?? false),
        ]);

        $templateId = $validated['template_id'] ?? null;
        if ($templateId) {
            $templateExists = Template::query()
                ->where('id', $templateId)
                ->where('empresa_id', $device->empresa_id)
                ->exists();

            if (! $templateExists) {
                return redirect()->back()->withInput()->withErrors([
                    'template_id' => 'Template inválido para a empresa da TV.',
                ]);
            }
        }

        DeviceConfiguration::query()->updateOrCreate(
            ['device_id' => $device->id],
            [
                'template_id' => $templateId,
                'atualizar_produtos_segundos' => $validated['atualizar_produtos_segundos'],
                'volume' => $validated['volume'],
                'orientacao' => $validated['orientacao'],
            ]
        );

        return redirect()
            ->route('admin.devices.index')
            ->with('success', 'TV atualizada com sucesso.');
    }

    private function authorizeDeviceAccess(Device $device): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        abort_unless((int) EmpresaContext::requireEmpresaId($user) === (int) $device->empresa_id, 403);
    }
}
