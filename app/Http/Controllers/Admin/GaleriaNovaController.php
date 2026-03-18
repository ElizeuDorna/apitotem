<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GaleriaNova;
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

        $galleries = GaleriaNova::query()
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

            $alreadyExists = GaleriaNova::query()
                ->where('source_type', 'upload')
                ->where('image_hash', $uploadedHash)
                ->exists();

            if ($alreadyExists) {
                return back()
                    ->withErrors(['upload_image' => 'Imagem ja cadastrada.'])
                    ->withInput();
            }
        }

        $gallery = GaleriaNova::create([
            'code' => $this->generateUniqueCode(),
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
            $path = $uploadImage->storeAs('galeria-nova', $fileName, 'public');

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

        if ($galeriaNova->source_type === 'upload' && $galeriaNova->file_path) {
            Storage::disk('public')->delete((string) $galeriaNova->file_path);
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
            return '/storage/'.ltrim((string) $item->file_path, '/');
        }

        return null;
    }
}
