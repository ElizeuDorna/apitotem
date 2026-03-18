<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Device;
use App\Models\DeviceConfiguration;
use App\Models\Empresa;
use App\Models\Template;
use App\Models\TemplateItem;
use App\Support\EmpresaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $empresaId = $this->resolveTemplateEmpresaIdForUser($user);

        $query = Template::query()
            ->withCount(['items', 'deviceConfigurations'])
            ->orderByDesc('is_default_web')
            ->orderByDesc('id');

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        } else {
            $query->whereRaw('1 = 0');
        }

        $devices = $empresaId
            ? Device::query()->where('empresa_id', $empresaId)->orderBy('nome')->get(['id', 'nome', 'local'])
            : collect();

        $defaultTemplate = $empresaId
            ? Template::query()->where('empresa_id', $empresaId)->where('is_default_web', true)->latest('id')->first()
            : null;

        return view('admin.templates.index', [
            'templates' => $query->paginate(15),
            'devices' => $devices,
            'defaultTemplate' => $defaultTemplate,
            'adminSemEmpresaAtiva' => $user->isDefaultAdmin() && ! $empresaId,
        ]);
    }

    public function create(): View
    {
        $user = Auth::user();
        $empresaId = $this->resolveTemplateEmpresaIdForUser($user);

        return view('admin.templates.create', [
            'layouts' => Template::LAYOUTS,
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'empresas' => $user->isDefaultAdmin()
                ? Empresa::query()->orderBy('NOME')->get(['id', 'NOME', 'CNPJ_CPF'])
                : collect(),
            'devices' => $empresaId
                ? Device::query()->where('empresa_id', $empresaId)->orderBy('nome')->get(['id', 'nome', 'local'])
                : collect(),
            'adminSemEmpresaAtiva' => $user->isDefaultAdmin() && ! $empresaId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo_layout' => ['required', Rule::in(Template::LAYOUTS)],
            'empresa_id' => ['nullable', 'integer', 'exists:empresa,id'],
            'capture_web_config' => ['nullable', 'boolean'],
            'is_default_web' => ['nullable', 'boolean'],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
        ]);

        $empresaId = $this->resolveTemplateEmpresaIdForStore($user, $validated);

        if (! $empresaId) {
            return redirect()->back()->withInput()->withErrors([
                'empresa_id' => 'Selecione uma empresa para criar o template.',
            ]);
        }

        $deviceId = $validated['device_id'] ?? null;
        if ($deviceId) {
            $this->validateDeviceBelongsToEmpresa($deviceId, $empresaId);
        }

        $template = Template::create([
            'empresa_id' => $empresaId,
            'nome' => $validated['nome'],
            'tipo_layout' => $validated['tipo_layout'],
            'web_config_payload' => $request->boolean('capture_web_config', true)
                ? $this->buildWebConfigSnapshot($empresaId)
                : null,
            'is_default_web' => false,
        ]);

        if ($request->boolean('is_default_web', false)) {
            $this->setDefaultTemplateForEmpresa($template);
        }

        if ($deviceId) {
            $this->assignTemplateToDevice($template, $deviceId);
        }

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Template criado com sucesso.');
    }

    public function edit(Template $template): View
    {
        $this->authorizeTemplate($template);

        $devices = Device::query()
            ->where('empresa_id', $template->empresa_id)
            ->orderBy('nome')
            ->get(['id', 'nome', 'local']);

        $assignedDeviceId = DeviceConfiguration::query()
            ->where('template_id', $template->id)
            ->orderByDesc('id')
            ->value('device_id');

        return view('admin.templates.edit', [
            'template' => $template,
            'layouts' => Template::LAYOUTS,
            'tipos' => TemplateItem::TIPOS,
            'items' => $template->items()->orderBy('ordem')->get(),
            'devices' => $devices,
            'assignedDeviceId' => $assignedDeviceId,
        ]);
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo_layout' => ['required', Rule::in(Template::LAYOUTS)],
            'capture_web_config' => ['nullable', 'boolean'],
            'is_default_web' => ['nullable', 'boolean'],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
        ]);

        $updateData = [
            'nome' => $validated['nome'],
            'tipo_layout' => $validated['tipo_layout'],
        ];

        if ($request->boolean('capture_web_config', false)) {
            $updateData['web_config_payload'] = $this->buildWebConfigSnapshot((int) $template->empresa_id);
        }

        $template->update($updateData);

        if ($request->boolean('is_default_web', false)) {
            $this->setDefaultTemplateForEmpresa($template);
        } else {
            $template->update(['is_default_web' => false]);
        }

        $deviceId = $validated['device_id'] ?? null;
        if ($deviceId) {
            $this->validateDeviceBelongsToEmpresa($deviceId, (int) $template->empresa_id);
            $this->assignTemplateToDevice($template, $deviceId);
        }

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Template atualizado.');
    }

    public function applyToDevice(Request $request, Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $validated = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,id'],
        ]);

        $this->validateDeviceBelongsToEmpresa((int) $validated['device_id'], (int) $template->empresa_id);
        $this->assignTemplateToDevice($template, (int) $validated['device_id']);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template aplicado ao dispositivo com sucesso.');
    }

    public function setDefaultWeb(Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);
        $this->setDefaultTemplateForEmpresa($template);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template padrão atualizado com sucesso.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template removido.');
    }

    public function addItem(Request $request, Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $validated = $request->validate([
            'tipo' => ['required', Rule::in(TemplateItem::TIPOS)],
            'ordem' => ['required', 'integer', 'min:1'],
            'conteudo' => ['nullable', 'string'],
            'tempo_exibicao' => ['nullable', 'integer', 'min:1', 'max:600'],
            'tamanho' => ['nullable', 'string', 'max:50'],
        ]);

        TemplateItem::create([
            'template_id' => $template->id,
            'tipo' => $validated['tipo'],
            'ordem' => $validated['ordem'],
            'conteudo' => $validated['conteudo'] ?? null,
            'config_json' => array_filter([
                'tempo_exibicao' => $validated['tempo_exibicao'] ?? null,
                'tamanho' => $validated['tamanho'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
        ]);

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Bloco adicionado ao template.');
    }

    public function deleteItem(Template $template, TemplateItem $item): RedirectResponse
    {
        $this->authorizeTemplate($template);
        abort_unless((int) $item->template_id === (int) $template->id, 404);

        $item->delete();

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Bloco removido.');
    }

    private function authorizeTemplate(Template $template): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        abort_unless((int) EmpresaContext::requireEmpresaId($user) === (int) $template->empresa_id, 403);
    }

    private function resolveTemplateEmpresaIdForUser($user): ?int
    {
        if ($user->isDefaultAdmin()) {
            return EmpresaContext::resolveEmpresaIdForUser($user);
        }

        return EmpresaContext::requireEmpresaId($user);
    }

    private function resolveTemplateEmpresaIdForStore($user, array $validated): ?int
    {
        if ($user->isDefaultAdmin()) {
            return (int) ($validated['empresa_id'] ?? EmpresaContext::resolveEmpresaIdForUser($user) ?? 0) ?: null;
        }

        return EmpresaContext::requireEmpresaId($user);
    }

    private function validateDeviceBelongsToEmpresa(int $deviceId, int $empresaId): void
    {
        $belongs = Device::query()
            ->where('id', $deviceId)
            ->where('empresa_id', $empresaId)
            ->exists();

        abort_unless($belongs, 422, 'Dispositivo inválido para a empresa selecionada.');
    }

    private function buildWebConfigSnapshot(int $empresaId): array
    {
        $config = Configuracao::query()->firstOrCreate(['empresa_id' => $empresaId], []);
        $payload = $config->toArray();

        unset($payload['id'], $payload['empresa_id'], $payload['created_at'], $payload['updated_at']);

        return $payload;
    }

    private function setDefaultTemplateForEmpresa(Template $template): void
    {
        Template::query()
            ->where('empresa_id', $template->empresa_id)
            ->where('id', '!=', $template->id)
            ->update(['is_default_web' => false]);

        $template->update(['is_default_web' => true]);
    }

    private function assignTemplateToDevice(Template $template, int $deviceId): void
    {
        $device = Device::query()->findOrFail($deviceId);

        $configuration = DeviceConfiguration::query()->firstOrCreate(
            ['device_id' => $device->id],
            [
                'atualizar_produtos_segundos' => 30,
                'volume' => 50,
                'orientacao' => 'landscape',
            ]
        );

        $configuration->template_id = $template->id;
        $configuration->save();
    }
}
