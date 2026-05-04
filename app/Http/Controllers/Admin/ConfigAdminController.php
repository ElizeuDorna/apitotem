<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\Empresa;
use App\Models\User;
use App\Support\EmpresaContext;
use App\Support\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConfigAdminController extends Controller
{
    public function edit()
    {
        $empresaId = $this->resolveEmpresaId();
        $panelBrandIconFeatureReady = Schema::hasColumn('configuracoes', 'panelBrandIconUrl');

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $config = $config->fresh();

        return view('admin.configadmin', compact('config', 'panelBrandIconFeatureReady'));
    }

    public function update(Request $request)
    {
        $empresaId = $this->resolveEmpresaId();
        $currentUser = Auth::user();
        $canManagePanelBranding = (bool) ($currentUser?->isDefaultAdmin()
            || $currentUser?->hasMenuAccess(User::MENU_CONFIGURACAO));
        $panelBrandIconFeatureReady = Schema::hasColumn('configuracoes', 'panelBrandIconUrl');

        if (! $canManagePanelBranding) {
            abort(403);
        }

        if (! $panelBrandIconFeatureReady) {
            return redirect()
                ->back()
                ->with('warning', 'Upload de icone indisponivel no momento. Execute as migrations pendentes no servidor e tente novamente.')
                ->withInput();
        }

        $validated = $request->validate([
            'panelBrandIconFile' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg,ico|max:2048',
            'removePanelBrandIcon' => 'nullable|boolean',
        ]);

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $payload = [];
        $shouldRemoveIcon = (bool) ($validated['removePanelBrandIcon'] ?? false);

        if ($shouldRemoveIcon) {
            $this->deletePanelBrandIcon($config->panelBrandIconUrl);
            $payload['panelBrandIconUrl'] = null;
        }

        if ($request->hasFile('panelBrandIconFile')) {
            $this->deletePanelBrandIcon($config->panelBrandIconUrl);
            $payload['panelBrandIconUrl'] = $this->storePanelBrandIcon($request->file('panelBrandIconFile'), $empresaId);
        }

        if ($payload !== []) {
            Configuracao::updateOrCreate(['empresa_id' => $empresaId], $payload);
        }

        return redirect()
            ->back()
            ->with('success', 'Configuracao do admin atualizada com sucesso.');
    }

    private function resolveEmpresaId(): int
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
