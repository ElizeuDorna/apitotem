<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadAsset;
use App\Models\User;
use App\Services\DownloadAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadAssetController extends Controller
{
    private const STORAGE_DISK = 'public';
    private const STORAGE_DIRECTORY = 'downloads';

    public function index(): View
    {
        $user = Auth::user();

        abort_unless($user?->hasMenuAccess(User::MENU_DOWNLOADS), 403);

        return view('admin.downloads.index', [
            'isDefaultAdmin' => (bool) $user?->isDefaultAdmin(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeDefaultAdmin();

        return view('admin.downloads.create');
    }

    public function store(Request $request, DownloadAssetService $downloadAssetService): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        $validated = $this->validatePayload($request);
        $downloadAssetService->create(
            (string) $validated['title'],
            $validated['description'] ?? null,
            $validated['file']
        );

        return redirect()
            ->route('admin.downloads.index')
            ->with('status', 'Arquivo de download criado com sucesso.');
    }

    public function edit(DownloadAsset $downloadAsset): View
    {
        $this->authorizeDefaultAdmin();

        return view('admin.downloads.edit', compact('downloadAsset'));
    }

    public function update(Request $request, DownloadAsset $downloadAsset): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        $validated = $this->validatePayload($request, $downloadAsset);
        $slug = $this->makeUniqueSlug((string) $validated['title'], $downloadAsset->id);

        $payload = [
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ];

        if (isset($validated['file']) && $validated['file'] instanceof UploadedFile) {
            $this->deleteStoredFile($downloadAsset->file_path);

            $file = $validated['file'];
            $payload['file_path'] = $this->storeFile($file, $slug);
            $payload['original_name'] = $file->getClientOriginalName();
            $payload['mime_type'] = $file->getClientMimeType();
            $payload['size_bytes'] = (int) $file->getSize();
        }

        $downloadAsset->update($payload);

        return redirect()
            ->route('admin.downloads.index')
            ->with('status', 'Arquivo de download atualizado com sucesso.');
    }

    public function destroy(DownloadAsset $downloadAsset): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        $this->deleteStoredFile($downloadAsset->file_path);
        $downloadAsset->delete();

        return redirect()
            ->route('admin.downloads.index')
            ->with('status', 'Arquivo de download removido com sucesso.');
    }

    public function publicIndex(): View
    {
        return view('downloads.public-index', [
            'downloads' => DownloadAsset::query()->ordered()->get(),
        ]);
    }

    public function download(DownloadAsset $downloadAsset): BinaryFileResponse
    {
        abort_unless(Storage::disk(self::STORAGE_DISK)->exists($downloadAsset->file_path), 404);

        return response()->download(
            Storage::disk(self::STORAGE_DISK)->path($downloadAsset->file_path),
            $downloadAsset->original_name,
            $downloadAsset->mime_type ? ['Content-Type' => $downloadAsset->mime_type] : []
        );
    }

    public function adminDownload(DownloadAsset $downloadAsset): BinaryFileResponse
    {
        $user = Auth::user();

        abort_unless($user?->hasMenuAccess(User::MENU_DOWNLOADS), 403);

        return $this->download($downloadAsset);
    }

    private function validatePayload(Request $request, ?DownloadAsset $downloadAsset = null): array
    {
        $fileRules = [File::default()->max(262144)];

        if ($downloadAsset === null) {
            array_unshift($fileRules, 'required');
        } else {
            array_unshift($fileRules, 'nullable');
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:160', Rule::unique('download_assets', 'title')->ignore($downloadAsset?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => $fileRules,
        ], [
            'title.required' => 'Informe o nome do arquivo para exibição.',
            'file.required' => 'Selecione um arquivo para upload.',
            'file.max' => 'O arquivo não pode ultrapassar 256 MB.',
        ]);
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'download';
        $slug = $baseSlug;
        $suffix = 2;

        while (DownloadAsset::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function storeFile(UploadedFile $file, string $slug): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $extension = $extension !== '' ? '.'.$extension : '';
        $fileName = $slug.'-'.Str::lower(Str::random(8)).$extension;

        return $file->storeAs(self::STORAGE_DIRECTORY, $fileName, self::STORAGE_DISK);
    }

    private function deleteStoredFile(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        Storage::disk(self::STORAGE_DISK)->delete($path);
    }

    private function authorizeDefaultAdmin(): void
    {
        abort_unless(Auth::user()?->isDefaultAdmin(), 403);
    }
}