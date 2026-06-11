<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\User;
use App\Support\EmpresaContext;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ConfigAdminController extends Controller
{
    private const APK_DISK = 'public';
    private const APK_PATH = 'apk/install.apk';
    private const SIDEBAR_FONT_FAMILY_OPTIONS = [
        'figtree',
        'inter',
        'roboto',
        'lato',
        'montserrat',
        'poppins',
        'open-sans',
        'source-sans-pro',
        'system-ui',
    ];

    public function edit()
    {
        $empresaId = $this->resolveEmpresaIdOrNull();
        $asaasConfigEmpresaId = $this->resolveAsaasConfigEmpresaIdOrNull();
        $panelBrandIconFeatureReady = Schema::hasColumn('configuracoes', 'panelBrandIconUrl');
        $panelSidebarFontFeatureReady = Schema::hasColumn('configuracoes', 'panelSidebarFontFamily')
            && Schema::hasColumn('configuracoes', 'panelSidebarFontSize');
        $produtoFormImagePreviewFeatureReady = Schema::hasColumn('configuracoes', 'produtoFormImagePreviewSize');
        $selfServiceLoginVisibilityFeatureReady = Schema::hasColumn('configuracoes', 'showSelfServiceRegisterOnLogin');
        $selfServiceDefaultPermissionsFeatureReady = Schema::hasColumn('configuracoes', 'selfServiceDefaultMenuPermissions');
        $asaasConfigFeatureReady = Schema::hasColumn('configuracoes', 'asaasBaseUrl')
            && Schema::hasColumn('configuracoes', 'asaasApiKey')
            && Schema::hasColumn('configuracoes', 'asaasWebhookToken');

        $config = $this->findOrCreateConfig($empresaId)->fresh();
        $globalConfig = $this->findOrCreateConfig(null)->fresh();
        $asaasConfig = $this->findOrCreateConfig($asaasConfigEmpresaId)->fresh();
        $selfServiceMenuOptions = User::availableMenuPermissions();

        $apkExists = Storage::disk(self::APK_DISK)->exists(self::APK_PATH);
        $apkSizeBytes = $apkExists ? (int) Storage::disk(self::APK_DISK)->size(self::APK_PATH) : null;
        $apkLastModified = $apkExists ? (int) Storage::disk(self::APK_DISK)->lastModified(self::APK_PATH) : null;
        $apkDownloadUrl = url('/install.apk');

        return view('admin.configadmin', compact(
            'config',
            'globalConfig',
            'panelBrandIconFeatureReady',
            'panelSidebarFontFeatureReady',
            'produtoFormImagePreviewFeatureReady',
            'selfServiceLoginVisibilityFeatureReady',
            'selfServiceDefaultPermissionsFeatureReady',
            'asaasConfigFeatureReady',
            'asaasConfig',
            'asaasConfigEmpresaId',
            'selfServiceMenuOptions',
            'apkExists',
            'apkSizeBytes',
            'apkLastModified',
            'apkDownloadUrl',
        ));
    }

    public function update(Request $request)
    {
        $empresaId = $this->resolveEmpresaIdOrNull();
        $currentUser = Auth::user();
        $canManagePanelBranding = (bool) ($currentUser?->isDefaultAdmin()
            || $currentUser?->hasMenuAccess(User::MENU_CONFIG_ADMIN));
        $panelBrandIconFeatureReady = Schema::hasColumn('configuracoes', 'panelBrandIconUrl');
        $panelSidebarFontFeatureReady = Schema::hasColumn('configuracoes', 'panelSidebarFontFamily')
            && Schema::hasColumn('configuracoes', 'panelSidebarFontSize');
        $selfServiceLoginVisibilityFeatureReady = Schema::hasColumn('configuracoes', 'showSelfServiceRegisterOnLogin');
        $selfServiceDefaultPermissionsFeatureReady = Schema::hasColumn('configuracoes', 'selfServiceDefaultMenuPermissions');
        $asaasConfigFeatureReady = Schema::hasColumn('configuracoes', 'asaasBaseUrl')
            && Schema::hasColumn('configuracoes', 'asaasApiKey')
            && Schema::hasColumn('configuracoes', 'asaasWebhookToken');

        if (! $canManagePanelBranding) {
            abort(403);
        }

        $validated = $request->validate([
            'panelBrandIconFile' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg,ico|max:2048',
            'removePanelBrandIcon' => 'nullable|boolean',
            'panelSidebarFontFamily' => 'nullable|string|in:'.implode(',', self::SIDEBAR_FONT_FAMILY_OPTIONS),
            'panelSidebarFontSize' => 'nullable|numeric|min:10|max:20',
            'produtoFormImagePreviewSize' => 'nullable|integer|min:32|max:300',
            'showSelfServiceRegisterOnLogin' => 'nullable|boolean',
            'selfServiceDefaultMenuPermissions' => ['nullable', 'array'],
            'selfServiceDefaultMenuPermissions.*' => ['string', Rule::in(array_keys(User::availableMenuPermissions()))],
            'metaAppId' => 'nullable|string|max:120',
            'metaRedirectUri' => 'nullable|url|max:500',
            'asaasBaseUrl' => 'nullable|url|max:255',
            'asaasApiKey' => 'nullable|string|max:5000',
            'asaasWebhookToken' => 'nullable|string|max:5000',
        ]);

        $config = $this->findOrCreateConfig($empresaId);
    $globalConfig = $this->findOrCreateConfig(null);
        $asaasConfig = $this->findOrCreateConfig($this->resolveAsaasConfigEmpresaIdOrNull());

        $payload = [];
        $asaasPayload = [];
        $shouldRemoveIcon = (bool) ($validated['removePanelBrandIcon'] ?? false);
        $warningMessages = [];

        if (($request->filled('metaAppId')
            || $request->filled('metaRedirectUri')
            || $request->has('showSelfServiceRegisterOnLogin')
            || $request->has('selfServiceDefaultMenuPermissions'))
            && ! $currentUser?->isDefaultAdmin()) {
            abort(403);
        }

        if ($panelSidebarFontFeatureReady) {
            $sidebarFontFamily = trim((string) ($validated['panelSidebarFontFamily'] ?? ''));
            $payload['panelSidebarFontFamily'] = $sidebarFontFamily !== '' ? $sidebarFontFamily : null;

            $sidebarFontSize = $validated['panelSidebarFontSize'] ?? null;
            $payload['panelSidebarFontSize'] = $sidebarFontSize !== null ? (float) $sidebarFontSize : null;
        } elseif ($request->filled('panelSidebarFontFamily') || $request->filled('panelSidebarFontSize')) {
            $warningMessages[] = 'Configuracao de fonte da lateral indisponivel no momento. Execute as migrations pendentes no servidor e tente novamente.';
        }

        $produtoFormImagePreviewSize = $validated['produtoFormImagePreviewSize'] ?? null;
        if ($produtoFormImagePreviewSize !== null) {
            $payload['produtoFormImagePreviewSize'] = (int) $produtoFormImagePreviewSize;
        }

        if ($selfServiceLoginVisibilityFeatureReady && $currentUser?->isDefaultAdmin()) {
            $globalConfig->showSelfServiceRegisterOnLogin = $request->boolean('showSelfServiceRegisterOnLogin');
        } elseif ($request->has('showSelfServiceRegisterOnLogin')) {
            $warningMessages[] = 'Controle do link de cadastro no login indisponivel no momento. Execute as migrations pendentes no servidor e tente novamente.';
        }

        if ($selfServiceDefaultPermissionsFeatureReady && $currentUser?->isDefaultAdmin()) {
            $globalConfig->selfServiceDefaultMenuPermissions = User::sanitizeMenuPermissions(
                $validated['selfServiceDefaultMenuPermissions'] ?? []
            );
        } elseif ($request->has('selfServiceDefaultMenuPermissions')) {
            $warningMessages[] = 'Configuracao das permissoes padrao do auto cadastro indisponivel no momento. Execute as migrations pendentes no servidor e tente novamente.';
        }

        if ($currentUser?->isDefaultAdmin()) {
            $metaAppId = trim((string) ($validated['metaAppId'] ?? ''));
            $metaRedirectUri = trim((string) ($validated['metaRedirectUri'] ?? ''));

            $globalConfig->fill([
                'metaAppId' => $metaAppId !== '' ? $metaAppId : null,
                'metaRedirectUri' => $metaRedirectUri !== '' ? $metaRedirectUri : null,
            ]);
        }

        if ($asaasConfigFeatureReady) {
            $asaasBaseUrl = trim((string) ($validated['asaasBaseUrl'] ?? ''));
            $asaasApiKey = trim((string) ($validated['asaasApiKey'] ?? ''));
            $asaasWebhookToken = trim((string) ($validated['asaasWebhookToken'] ?? ''));

            $asaasPayload['asaasBaseUrl'] = $asaasBaseUrl !== '' ? $asaasBaseUrl : null;
            $asaasPayload['asaasApiKey'] = $asaasApiKey !== '' ? $asaasApiKey : null;
            $asaasPayload['asaasWebhookToken'] = $asaasWebhookToken !== '' ? $asaasWebhookToken : null;
        } elseif ($request->filled('asaasBaseUrl') || $request->filled('asaasApiKey') || $request->filled('asaasWebhookToken')) {
            $warningMessages[] = 'Configuracao do Asaas indisponivel no momento. Execute as migrations pendentes no servidor e tente novamente.';
        }

        if ($panelBrandIconFeatureReady) {
            if ($shouldRemoveIcon) {
                $this->deletePanelBrandIcon($config->panelBrandIconUrl);
                $payload['panelBrandIconUrl'] = null;
            }

            if ($request->hasFile('panelBrandIconFile')) {
                $this->deletePanelBrandIcon($config->panelBrandIconUrl);
                $payload['panelBrandIconUrl'] = $this->storePanelBrandIcon($request->file('panelBrandIconFile'), $empresaId);
            }
        } elseif ($shouldRemoveIcon || $request->hasFile('panelBrandIconFile')) {
            $warningMessages[] = 'Upload de icone indisponivel no momento. Execute as migrations pendentes no servidor e tente novamente.';
        }

        if ($payload !== []) {
            $config->fill($payload)->save();
        }

        if ($currentUser?->isDefaultAdmin()) {
            $globalConfig->save();
        }

        if ($asaasPayload !== []) {
            $asaasConfig->fill($asaasPayload)->save();
        }

        $redirect = redirect()
            ->back()
            ->with('success', 'Configuracao do admin atualizada com sucesso.');

        if ($warningMessages !== []) {
            $redirect->with('warning', implode(' ', $warningMessages));
        }

        return $redirect;
    }

    private function resolveEmpresaIdOrNull(): ?int
    {
        $user = Auth::user();

        if ($user && $user->isDefaultAdmin()) {
            return EmpresaContext::resolveEmpresaIdForUser($user);
        }

        return (int) EmpresaContext::requireEmpresaId($user);
    }

    private function resolveAsaasConfigEmpresaIdOrNull(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->isDefaultAdmin()) {
            return null;
        }

        $empresa = EmpresaContext::resolveEmpresaForUser($user);

        return $empresa ? (int) $empresa->id : null;
    }

    private function findOrCreateConfig(?int $empresaId): Configuracao
    {
        $query = Configuracao::query();

        if ($empresaId !== null) {
            $query->where('empresa_id', $empresaId);
        } else {
            $query->whereNull('empresa_id');
        }

        $config = $query->first();

        if (! $config) {
            $config = new Configuracao();
            $config->empresa_id = $empresaId;
            $config->save();
        }

        return $config;
    }

    private function storePanelBrandIcon($upload, ?int $empresaId): string
    {
        $document = $this->resolveEmpresaDocument($empresaId);
        $extension = strtolower((string) $upload->getClientOriginalExtension());
        $extension = $extension !== '' ? $extension : 'png';

        $baseName = pathinfo((string) $upload->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName = Str::slug((string) $baseName);
        if ($safeBaseName === '') {
            $safeBaseName = 'icone-painel';
        }

        $fileName = sprintf('%s-%s.%s', $safeBaseName, Str::lower(Str::random(8)), $extension);
        $path = $upload->storeAs('empresas/'.$document.'/branding', $fileName, ImageStorage::disk());

        return ImageStorage::publicUrl($path);
    }

    private function deletePanelBrandIcon(?string $iconUrl): void
    {
        $relativePath = ImageStorage::extractRelativePathFromPublicUrl((string) $iconUrl);

        if ($relativePath === '') {
            return;
        }

        Storage::disk(ImageStorage::disk())->delete($relativePath);
    }

    public function storeApk(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isDefaultAdmin(), 403);

        $validated = $request->validate([
            'apk_file' => ['required', File::types(['apk'])->max(262144)],
        ], [
            'apk_file.required' => 'Envie o arquivo APK.',
            'apk_file.max' => 'O APK nao pode ultrapassar 256 MB.',
        ]);

        Storage::disk(self::APK_DISK)->putFileAs('apk', $validated['apk_file'], 'install.apk');

        return redirect()
            ->route('admin.configadmin.edit')
            ->with('success', 'APK enviado com sucesso e publicado em /install.apk');
    }

    private function resolveEmpresaDocument(?int $empresaId): string
    {
        if ($empresaId === null) {
            return 'admin-global';
        }

        $rawDocument = (string) Empresa::query()->whereKey($empresaId)->value('cnpj_cpf');
        $normalizedDocument = preg_replace('/\D/', '', $rawDocument) ?? '';

        return $normalizedDocument !== '' ? $normalizedDocument : 'empresa-'.$empresaId;
    }
}
