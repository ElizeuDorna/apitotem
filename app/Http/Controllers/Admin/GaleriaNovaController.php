<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\GaleriaNova;
use App\Support\EmpresaContext;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GaleriaNovaController extends Controller
{
    public function index(): View
    {
        $this->authorizeGaleriaNovaAccess();

        $empresaId = $this->resolveCurrentEmpresaId();

        $galleries = GaleriaNova::query()
            ->when($empresaId !== null, function ($query) use ($empresaId) {
                $query->where(function ($subQuery) use ($empresaId) {
                    $subQuery->where('empresa_id', $empresaId)
                        ->orWhereNull('empresa_id')
                        ->orWhere('is_public', true);
                });
            })
            ->orderByDesc('id')
            ->paginate(18);

        return view('admin.galeria-nova.index', compact('galleries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeGaleriaNovaAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'external_url' => ['nullable', 'url', 'max:1000'],
            'upload_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $externalUrl = trim((string) ($validated['external_url'] ?? ''));
        $uploadImage = $request->file('upload_image');
        $uploadedHash = null;

        if ($externalUrl === '' && ! $uploadImage) {
            return back()
                ->withErrors(['upload_image' => 'Informe um link externo ou envie uma imagem por upload.'])
                ->withInput();
        }

        if ($uploadImage) {
            $uploadedHash = hash_file('sha256', (string) $uploadImage->getRealPath());
            $empresaId = $this->resolveCurrentEmpresaId();

            $alreadyExists = GaleriaNova::query()
                ->where('source_type', 'upload')
                ->where('image_hash', $uploadedHash)
                ->when($empresaId !== null, fn ($query) => $query->where('empresa_id', $empresaId))
                ->when($empresaId === null, fn ($query) => $query->whereNull('empresa_id'))
                ->exists();

            if ($alreadyExists) {
                return back()
                    ->withErrors(['upload_image' => 'Imagem ja cadastrada.'])
                    ->withInput();
            }
        }

        $gallery = GaleriaNova::create([
            'code' => $this->generateUniqueCode(),
            'empresa_id' => $this->resolveCurrentEmpresaId(),
            'is_public' => (bool) ($validated['is_public'] ?? false),
            'name' => (string) $validated['name'],
            'source_type' => $uploadImage ? 'upload' : 'link',
            'external_url' => $uploadImage ? null : $externalUrl,
            'file_path' => null,
            'image_hash' => $uploadedHash,
            'created_by' => Auth::id(),
        ]);

        if ($uploadImage) {
            $extension = strtolower((string) $uploadImage->getClientOriginalExtension());
            $fileName = $gallery->code.($extension !== '' ? '.'.$extension : '');
            $document = $this->resolveStorageDocument($gallery->empresa_id);
            $path = $uploadImage->storeAs('empresas/'.$document.'/galeria', $fileName, ImageStorage::disk());

            $gallery->update([
                'file_path' => $path,
                'external_url' => null,
            ]);
        }

        return redirect()
            ->route('admin.galeria-imagem.index')
            ->with('status', 'Imagem adicionada na Galeria de Imagem com sucesso.');
    }

    public function destroy(GaleriaNova $galeriaNova): RedirectResponse
    {
        $this->authorizeGaleriaNovaAccess();

        if (! $this->canManageGalleryItem($galeriaNova)) {
            abort(403);
        }

        if ($galeriaNova->source_type === 'upload' && $galeriaNova->file_path) {
            Storage::disk(ImageStorage::disk())->delete((string) $galeriaNova->file_path);
        }

        $galeriaNova->delete();

        return redirect()
            ->route('admin.galeria-imagem.index')
            ->with('status', 'Imagem removida da Galeria de Imagem com sucesso.');
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = (string) random_int(10000000000000, 99999999999999);
            $exists = GaleriaNova::query()->where('code', $code)->exists();
        } while ($exists);

        return $code;
    }

    private function authorizeGaleriaNovaAccess(): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if (
            $user->isDefaultAdmin()
            || $user->hasMenuAccess('produtos')
            || $user->hasMenuAccess('rede_social')
            || $user->hasMenuAccess('configuracao')
        ) {
            return;
        }

        abort(403);
    }

    public static function itemUrl(GaleriaNova $item): ?string
    {
        if ($item->source_type === 'link') {
            $url = trim((string) ($item->external_url ?? ''));

            return $url !== '' ? $url : null;
        }

        if ($item->source_type === 'upload' && ! empty($item->file_path)) {
            return ImageStorage::publicUrl((string) $item->file_path);
        }

        return null;
    }

    private function resolveCurrentEmpresaId(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return EmpresaContext::resolveEmpresaIdForUser($user);
    }

    private function resolveStorageDocument(?int $empresaId): string
    {
        if (! $empresaId) {
            return 'global';
        }

        $empresa = Empresa::query()->find($empresaId);
        $document = preg_replace('/\D/', '', (string) ($empresa?->cnpj_cpf ?? ''));

        return $document !== '' ? $document : (string) $empresaId;
    }

    private function canManageGalleryItem(GaleriaNova $gallery): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isDefaultAdmin()) {
            return true;
        }

        $empresaId = $this->resolveCurrentEmpresaId();

        return $empresaId !== null && (int) $gallery->empresa_id === (int) $empresaId;
    }
}
