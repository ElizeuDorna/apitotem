<?php

namespace App\Livewire\Admin;

use App\Models\Configuracao;
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

    public bool $asaasConfigFeatureReady = false;

    public bool $apkExists = false;

    public ?int $apkSizeBytes = null;

    public ?int $apkLastModified = null;

    public string $apkDownloadUrl = '';

    public array $selfServiceMenuOptions = [];

    public ?string $openSection = 'cadastro-login';

    public function mount(
        Configuracao $config,
        Configuracao $globalConfig,
        Configuracao $asaasConfig,
        bool $panelBrandIconFeatureReady,
        bool $panelSidebarFontFeatureReady,
        bool $produtoFormImagePreviewFeatureReady,
        bool $selfServiceLoginVisibilityFeatureReady,
        bool $selfServiceDefaultPermissionsFeatureReady,
        bool $asaasConfigFeatureReady,
        bool $apkExists,
        ?int $apkSizeBytes,
        ?int $apkLastModified,
        string $apkDownloadUrl,
        array $selfServiceMenuOptions
    ): void {
        $this->config = $config;
        $this->globalConfig = $globalConfig;
        $this->asaasConfig = $asaasConfig;
        $this->panelBrandIconFeatureReady = $panelBrandIconFeatureReady;
        $this->panelSidebarFontFeatureReady = $panelSidebarFontFeatureReady;
        $this->produtoFormImagePreviewFeatureReady = $produtoFormImagePreviewFeatureReady;
        $this->selfServiceLoginVisibilityFeatureReady = $selfServiceLoginVisibilityFeatureReady;
        $this->selfServiceDefaultPermissionsFeatureReady = $selfServiceDefaultPermissionsFeatureReady;
        $this->asaasConfigFeatureReady = $asaasConfigFeatureReady;
        $this->apkExists = $apkExists;
        $this->apkSizeBytes = $apkSizeBytes;
        $this->apkLastModified = $apkLastModified;
        $this->apkDownloadUrl = $apkDownloadUrl;
        $this->selfServiceMenuOptions = $selfServiceMenuOptions;
    }

    public function toggleSection(string $section): void
    {
        $this->openSection = $this->openSection === $section ? null : $section;
    }

    public function render()
    {
        return view('livewire.admin.config-admin-panel');
    }
}