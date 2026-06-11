<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Config Admin
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <livewire:admin.config-admin-panel
                :config="$config"
                :asaas-config="$asaasConfig"
                :panel-brand-icon-feature-ready="$panelBrandIconFeatureReady"
                :panel-sidebar-font-feature-ready="$panelSidebarFontFeatureReady"
                :produto-form-image-preview-feature-ready="$produtoFormImagePreviewFeatureReady"
                :self-service-login-visibility-feature-ready="$selfServiceLoginVisibilityFeatureReady"
                :asaas-config-feature-ready="$asaasConfigFeatureReady"
                :apk-exists="$apkExists"
                :apk-size-bytes="$apkSizeBytes"
                :apk-last-modified="$apkLastModified"
                :apk-download-url="$apkDownloadUrl"
            />
        </div>
    </div>
</x-app-layout>
