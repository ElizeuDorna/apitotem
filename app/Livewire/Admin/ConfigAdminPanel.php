<?php

namespace App\Livewire\Admin;

use App\Models\Configuracao;
use Livewire\Component;

class ConfigAdminPanel extends Component
{
    public Configuracao $config;

    public Configuracao $asaasConfig;

    public bool $panelBrandIconFeatureReady = false;

    public bool $panelSidebarFontFeatureReady = false;

    public bool $produtoFormImagePreviewFeatureReady = false;

    public bool $asaasConfigFeatureReady = false;

    public bool $apkExists = false;

    public ?int $apkSizeBytes = null;

    public ?int $apkLastModified = null;

    public string $apkDownloadUrl = '';

    public ?string $openSection = null;

    public function mount(
        Configuracao $config,
        Configuracao $asaasConfig,
        bool $panelBrandIconFeatureReady,
        bool $panelSidebarFontFeatureReady,
        bool $produtoFormImagePreviewFeatureReady,
        bool $asaasConfigFeatureReady,
        bool $apkExists,
        ?int $apkSizeBytes,
        ?int $apkLastModified,
        string $apkDownloadUrl
    ): void {
        $this->config = $config;
        $this->asaasConfig = $asaasConfig;
        $this->panelBrandIconFeatureReady = $panelBrandIconFeatureReady;
        $this->panelSidebarFontFeatureReady = $panelSidebarFontFeatureReady;
        $this->produtoFormImagePreviewFeatureReady = $produtoFormImagePreviewFeatureReady;
        $this->asaasConfigFeatureReady = $asaasConfigFeatureReady;
        $this->apkExists = $apkExists;
        $this->apkSizeBytes = $apkSizeBytes;
        $this->apkLastModified = $apkLastModified;
        $this->apkDownloadUrl = $apkDownloadUrl;
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