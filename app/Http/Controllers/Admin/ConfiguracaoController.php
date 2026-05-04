<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\User;
use App\Support\ImageStorage;
use App\Support\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConfiguracaoController extends Controller
{
    /**
     * Show the configuration form.
     */
    public function edit()
    {
        $empresaId = $this->resolveEmpresaId();

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $config = $config->fresh();

        return view('admin.configuracao', compact('config'));
    }

    /**
     * Handle form submission and update configuration.
     */
    public function update(Request $request)
    {
        $empresaId = $this->resolveEmpresaId();
        $currentUser = Auth::user();
        $canManagePanelBranding = (bool) ($currentUser?->isDefaultAdmin()
            || $currentUser?->hasMenuAccess(User::MENU_CONFIGURACAO));

        if (! $canManagePanelBranding && ($request->hasFile('panelBrandIconFile') || $request->boolean('removePanelBrandIcon'))) {
            abort(403);
        }

        $validated = $request->validate([
            'apiUrl' => 'nullable|string|max:500',
            'priceColor' => 'nullable|string|max:7',
            'offerColor' => 'nullable|string|max:7',
            'rowBackgroundColor' => 'nullable|string|max:9',
            'showBorder' => 'nullable|boolean',
            'borderColor' => 'nullable|string|max:7',
            'useGradient' => 'nullable|boolean',
            'gradientStartColor' => 'nullable|string|max:9',
            'gradientEndColor' => 'nullable|string|max:9',
            'gradientStop1' => 'nullable|numeric|min:0|max:1',
            'gradientStop2' => 'nullable|numeric|min:0|max:1',
            'appBackgroundColor' => 'nullable|string|max:9',
            'isMainBorderEnabled' => 'nullable|boolean',
            'mainBorderColor' => 'nullable|string|max:7',
            'showImage' => 'nullable|boolean',
            'imageSize' => 'nullable|integer|min:16|max:512',
            'isPaginationEnabled' => 'nullable|boolean',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'paginationInterval' => 'nullable|integer|min:1|max:60',
            'apiRefreshInterval' => 'nullable|integer|min:5|max:3600',
            'panelBrandIconFile' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg,ico|max:2048',
            'removePanelBrandIcon' => 'nullable|boolean',
        ]);

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $payload = $validated;
        unset($payload['panelBrandIconFile'], $payload['removePanelBrandIcon']);

        if ($canManagePanelBranding) {
            $shouldRemoveIcon = (bool) ($validated['removePanelBrandIcon'] ?? false);

            if ($shouldRemoveIcon) {
                $this->deletePanelBrandIcon($config->panelBrandIconUrl);
                $payload['panelBrandIconUrl'] = null;
            }

            if ($request->hasFile('panelBrandIconFile')) {
                $this->deletePanelBrandIcon($config->panelBrandIconUrl);
                $payload['panelBrandIconUrl'] = $this->storePanelBrandIcon($request->file('panelBrandIconFile'), $empresaId);
            }
        }

        Configuracao::updateOrCreate(['empresa_id' => $empresaId], $payload);

        return redirect()
            ->back()
            ->with('success', 'Configuração atualizada com sucesso.');
    }

    private function resolveEmpresaId(): ?int
    {
        $user = Auth::user();

        return (int) EmpresaContext::requireEmpresaId($user);
    }

    private function storePanelBrandIcon($upload, int $empresaId): string
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

    private function resolveEmpresaDocument(int $empresaId): string
    {
        $rawDocument = (string) Empresa::query()->whereKey($empresaId)->value('cnpj_cpf');
        $normalizedDocument = preg_replace('/\D/', '', $rawDocument) ?? '';

        return $normalizedDocument !== '' ? $normalizedDocument : 'empresa-'.$empresaId;
    }
}
