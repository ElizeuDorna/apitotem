<?php

namespace App\Livewire\Admin;

use App\Models\Configuracao;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ConfigAdminPanel extends Component
{
    public Configuracao $config;

    public Configuracao $globalConfig;

    public Configuracao $asaasConfig;

    public bool $panelBrandIconFeatureReady = false;

    public bool $panelSidebarFontFeatureReady = false;

    public bool $produtoFormImagePreviewFeatureReady = false;

    public bool $selfServiceLoginVisibilityFeatureReady = false;

    public bool $selfServiceDefaultPermissionsFeatureReady = false;

    public bool $selfServiceDefaultWebScreenModelFeatureReady = false;

    public bool $selfServiceCloneDefaultWebScreenModelFeatureReady = false;

    public bool $asaasConfigFeatureReady = false;

    public bool $apkExists = false;

    public ?int $apkSizeBytes = null;

    public ?int $apkLastModified = null;

    public string $apkDownloadUrl = '';

    public array $selfServiceMenuOptions = [];

    public array $selfServiceWebScreenModelOptions = [];

    public bool $canManageAsaasSection = false;

    public ?string $openSection = 'tema';

    public function mount(
        Configuracao $config,
        Configuracao $globalConfig,
        Configuracao $asaasConfig,
        bool $panelBrandIconFeatureReady,
        bool $panelSidebarFontFeatureReady,
        bool $produtoFormImagePreviewFeatureReady,
        bool $selfServiceLoginVisibilityFeatureReady,
        bool $selfServiceDefaultPermissionsFeatureReady,
        bool $selfServiceDefaultWebScreenModelFeatureReady,
        bool $selfServiceCloneDefaultWebScreenModelFeatureReady,
        bool $asaasConfigFeatureReady,
        bool $apkExists,
        ?int $apkSizeBytes,
        ?int $apkLastModified,
        string $apkDownloadUrl,
        array $selfServiceMenuOptions,
        array $selfServiceWebScreenModelOptions
    ): void {
        $this->config = $config;
        $this->globalConfig = $globalConfig;
        $this->asaasConfig = $asaasConfig;
        $this->panelBrandIconFeatureReady = $panelBrandIconFeatureReady;
        $this->panelSidebarFontFeatureReady = $panelSidebarFontFeatureReady;
        $this->produtoFormImagePreviewFeatureReady = $produtoFormImagePreviewFeatureReady;
        $this->selfServiceLoginVisibilityFeatureReady = $selfServiceLoginVisibilityFeatureReady;
        $this->selfServiceDefaultPermissionsFeatureReady = $selfServiceDefaultPermissionsFeatureReady;
        $this->selfServiceDefaultWebScreenModelFeatureReady = $selfServiceDefaultWebScreenModelFeatureReady;
        $this->selfServiceCloneDefaultWebScreenModelFeatureReady = $selfServiceCloneDefaultWebScreenModelFeatureReady;
        $this->asaasConfigFeatureReady = $asaasConfigFeatureReady;
        $this->apkExists = $apkExists;
        $this->apkSizeBytes = $apkSizeBytes;
        $this->apkLastModified = $apkLastModified;
        $this->apkDownloadUrl = $apkDownloadUrl;
        $this->selfServiceMenuOptions = $selfServiceMenuOptions;
        $this->selfServiceWebScreenModelOptions = $selfServiceWebScreenModelOptions;
        $user = Auth::user();
        $linkedEmpresa = $user ? EmpresaContext::resolveEmpresaForUser($user) : null;

        $this->canManageAsaasSection = (bool) (
            $user?->isDefaultAdmin()
            || (
                $user?->hasMenuAccess(User::MENU_CONFIG_ADMIN_ASAAS)
                && ! $linkedEmpresa?->isClienteFinal()
            )
        );
        $this->openSection = $user?->isDefaultAdmin() ? 'cadastro-login' : 'tema';
    }

    public function toggleSection(string $section): void
    {
        if ($section === 'cadastro-login' && ! Auth::user()?->isDefaultAdmin()) {
            return;
        }

        if ($section === 'asaas' && ! $this->canManageAsaasSection) {
            return;
        }

        $this->openSection = $this->openSection === $section ? null : $section;
    }

    public function render()
    {
        return view('livewire.admin.config-admin-panel');
    }
}