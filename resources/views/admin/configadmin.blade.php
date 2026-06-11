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
                :global-config="$globalConfig"
                :asaas-config="$asaasConfig"
                :panel-brand-icon-feature-ready="$panelBrandIconFeatureReady"
                :panel-sidebar-font-feature-ready="$panelSidebarFontFeatureReady"
                :produto-form-image-preview-feature-ready="$produtoFormImagePreviewFeatureReady"
                :self-service-login-visibility-feature-ready="$selfServiceLoginVisibilityFeatureReady"
                :self-service-default-permissions-feature-ready="$selfServiceDefaultPermissionsFeatureReady"
                :self-service-default-web-screen-model-feature-ready="$selfServiceDefaultWebScreenModelFeatureReady"
                :asaas-config-feature-ready="$asaasConfigFeatureReady"
                :apk-exists="$apkExists"
                :apk-size-bytes="$apkSizeBytes"
                :apk-last-modified="$apkLastModified"
                :apk-download-url="$apkDownloadUrl"
                :self-service-menu-options="$selfServiceMenuOptions"
                :self-service-web-screen-model-options="$selfServiceWebScreenModelOptions"
            />
        </div>
    </div>
</x-app-layout>
