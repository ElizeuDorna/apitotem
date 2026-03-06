<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalImageGallery;
use App\Models\GlobalImageGalleryItem;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GlobalImageGalleryController extends Controller
{
    public function searchByName(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json(['items' => []]);
        }

        $items = GlobalImageGallery::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit(20)
            ->get(['code', 'name'])
            ->map(fn (GlobalImageGallery $gallery) => [
                'code' => (string) $gallery->code,
                'name' => (string) $gallery->name,
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    public function lookupByCode(string $code): JsonResponse
    {
        $normalizedCode = substr(preg_replace('/\D/', '', $code) ?? '', 0, 14);
        $companyId = (int) (Auth::user()?->empresa_id ?? 0);
        $matchedProduct = null;

        if ($normalizedCode !== '' && $companyId > 0) {
            $matchedProduct = Produto::query()
                ->where('empresa_id', $companyId)
                ->where('CODIGO', $normalizedCode)
                ->first(['CODIGO', 'NOME']);
        }

        if ($normalizedCode === '') {
            return response()->json([
                'found' => false,
                'code' => '',
                'name' => null,
                'images' => [],
                'productFound' => false,
                'productName' => null,
            ]);
        }

        $gallery = GlobalImageGallery::query()
            ->where('code', $normalizedCode)
            ->with('items')
            ->first();

        if (! $gallery) {
            return response()->json([
                'found' => false,
                'code' => $normalizedCode,
                'name' => null,
                'images' => [],
                'productFound' => $matchedProduct !== null,
                'productName' => $matchedProduct?->NOME,
            ]);
        }

        $images = $this->resolvedImageUrlsBySlot($gallery)
            ->map(fn ($url, $slotKey) => [
                'slot' => (int) str_replace('slot_', '', $slotKey),
                'slotKey' => $slotKey,
                'url' => $url,
            ])
            ->values()
            ->all();

        return response()->json([
            'found' => true,
            'code' => $gallery->code,
            'name' => $gallery->name,
            'images' => $images,
            'productFound' => $matchedProduct !== null,
            'productName' => $matchedProduct?->NOME,
        ]);
    }

    public function index(): View
    {
        $this->authorizeDefaultAdmin();

        $galleries = GlobalImageGallery::query()
            ->with('items')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.global-image-galleries.index', compact('galleries'));
    }

    public function create(): View
    {
        $this->authorizeDefaultAdmin();

        return view('admin.global-image-galleries.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        $validated = $this->validateRequest($request);
        $code = $this->normalizeCode($validated['code'] ?? '');

        DB::transaction(function () use ($validated, $code, $request) {
            $gallery = GlobalImageGallery::create([
                'code' => $code,
                'name' => (string) $validated['name'],
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($gallery, $request);
        });

        return redirect()
            ->route('admin.global-image-galleries.index')
            ->with('status', 'Galeria global criada com sucesso.');
    }

    public function edit(GlobalImageGallery $globalImageGallery): View
    {
        $this->authorizeDefaultAdmin();

        $globalImageGallery->load('items');

        return view('admin.global-image-galleries.edit', [
            'gallery' => $globalImageGallery,
        ]);
    }

    public function update(Request $request, GlobalImageGallery $globalImageGallery): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        $validated = $this->validateRequest($request, $globalImageGallery->id);
        $newCode = $this->normalizeCode($validated['code'] ?? '');
        $oldCode = (string) $globalImageGallery->code;

        DB::transaction(function () use ($validated, $newCode, $oldCode, $globalImageGallery, $request) {
            if ($newCode !== $oldCode) {
                $this->renameUploadedFilesForCodeChange($oldCode, $newCode, $globalImageGallery);
            }

            $globalImageGallery->update([
                'code' => $newCode,
                'name' => (string) $validated['name'],
            ]);

            $this->syncItems($globalImageGallery->fresh(), $request);
        });

        return redirect()
            ->route('admin.global-image-galleries.index')
            ->with('status', 'Galeria global atualizada com sucesso.');
    }

    public function destroy(GlobalImageGallery $globalImageGallery): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        $globalImageGallery->load('items');

        foreach ($globalImageGallery->items as $item) {
            if ($item->source_type === 'upload' && $item->file_path) {
                Storage::disk('public')->delete($item->file_path);
            }
        }

        $globalImageGallery->delete();

        return redirect()
            ->route('admin.global-image-galleries.index')
            ->with('status', 'Galeria global removida com sucesso.');
    }

    private function validateRequest(Request $request, ?int $galleryId = null): array
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'regex:/^\d{1,14}$/',
                Rule::unique('global_image_galleries', 'code')->ignore($galleryId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'source_type' => ['nullable', 'array'],
            'source_type.*' => ['nullable', Rule::in(['none', 'link', 'upload'])],
            'external_url' => ['nullable', 'array'],
            'external_url.*' => ['nullable', 'url', 'max:1000'],
            'upload_image' => ['nullable', 'array'],
            'upload_image.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $errors = [];
        $sourceTypes = (array) $request->input('source_type', []);
        $externalUrls = (array) $request->input('external_url', []);
        $uploads = (array) $request->file('upload_image', []);

        foreach ([1, 2, 3] as $slot) {
            $sourceType = (string) ($sourceTypes[$slot] ?? 'none');
            $externalUrl = trim((string) ($externalUrls[$slot] ?? ''));
            $upload = $uploads[$slot] ?? null;

            if ($sourceType === 'link' && $externalUrl === '') {
                $errors['external_url.'.$slot] = 'Informe uma URL para o slot '.$slot.' quando a origem for link.';
            }

            if ($sourceType === 'upload' && ! $upload && $galleryId === null) {
                $errors['upload_image.'.$slot] = 'Envie uma imagem para o slot '.$slot.' quando a origem for upload.';
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    private function syncItems(GlobalImageGallery $gallery, Request $request): void
    {
        $sourceTypes = (array) $request->input('source_type', []);
        $externalUrls = (array) $request->input('external_url', []);
        $uploads = $request->file('upload_image', []);

        foreach ([1, 2, 3] as $slot) {
            $sourceType = (string) ($sourceTypes[$slot] ?? 'none');
            $externalUrl = trim((string) ($externalUrls[$slot] ?? ''));
            $upload = $uploads[$slot] ?? null;

            /** @var GlobalImageGalleryItem|null $existing */
            $existing = $gallery->items()->where('slot', $slot)->first();

            if ($sourceType === 'none') {
                if ($existing) {
                    $this->deleteItemFileIfNeeded($existing);
                    $existing->delete();
                }
                continue;
            }

            if ($sourceType === 'link') {
                if ($existing && $existing->source_type === 'upload' && $existing->file_path) {
                    Storage::disk('public')->delete($existing->file_path);
                }

                $gallery->items()->updateOrCreate(
                    ['slot' => $slot],
                    [
                        'source_type' => 'link',
                        'external_url' => $externalUrl !== '' ? $externalUrl : null,
                        'file_path' => null,
                    ]
                );

                continue;
            }

            if (! $upload && $existing && $existing->source_type === 'upload') {
                continue;
            }

            if (! $upload) {
                if ($existing) {
                    $this->deleteItemFileIfNeeded($existing);
                    $existing->delete();
                }
                continue;
            }

            $extension = strtolower((string) $upload->getClientOriginalExtension());
            $fileName = $gallery->code.'_'.$slot.'.'.$extension;
            $path = $upload->storeAs('galeria-geral', $fileName, 'public');

            if ($existing && $existing->source_type === 'upload' && $existing->file_path && $existing->file_path !== $path) {
                Storage::disk('public')->delete($existing->file_path);
            }

            $gallery->items()->updateOrCreate(
                ['slot' => $slot],
                [
                    'source_type' => 'upload',
                    'external_url' => null,
                    'file_path' => $path,
                ]
            );
        }
    }

    private function renameUploadedFilesForCodeChange(string $oldCode, string $newCode, GlobalImageGallery $gallery): void
    {
        $gallery->load('items');

        foreach ($gallery->items as $item) {
            if ($item->source_type !== 'upload' || ! $item->file_path) {
                continue;
            }

            $oldPath = (string) $item->file_path;
            $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
            $newPath = 'galeria-geral/'.$newCode.'_'.$item->slot.($extension ? '.'.$extension : '');

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->move($oldPath, $newPath);
                $item->update(['file_path' => $newPath]);
            }
        }
    }

    private function deleteItemFileIfNeeded(GlobalImageGalleryItem $item): void
    {
        if ($item->source_type === 'upload' && $item->file_path) {
            Storage::disk('public')->delete($item->file_path);
        }
    }

    private function normalizeCode(string $code): string
    {
        return substr(preg_replace('/\D/', '', $code) ?? '', 0, 14);
    }

    private function resolvedImageUrlsBySlot(GlobalImageGallery $gallery)
    {
        return $gallery->items
            ->sortBy('slot')
            ->mapWithKeys(function (GlobalImageGalleryItem $item) {
                $slotKey = 'slot_'.(int) $item->slot;

                if ($item->source_type === 'link') {
                    return [$slotKey => trim((string) ($item->external_url ?? ''))];
                }

                if ($item->source_type === 'upload' && !empty($item->file_path)) {
                    return [$slotKey => $this->publicStorageUrl((string) $item->file_path)];
                }

                return [$slotKey => ''];
            })
            ->filter(fn ($url) => $url !== '');
    }

    private function authorizeDefaultAdmin(): void
    {
        abort_unless(Auth::user()?->isDefaultAdmin(), 403);
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }
}
