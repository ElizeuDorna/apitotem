<?php

namespace App\Services;

use App\Models\DownloadAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DownloadAssetService
{
    private const STORAGE_DISK = 'public';
    private const STORAGE_DIRECTORY = 'downloads';

    public function create(string $title, ?string $description, UploadedFile $file): DownloadAsset
    {
        $slug = $this->makeUniqueSlug($title);
        $path = $this->storeFile($file, $slug);

        return DownloadAsset::query()->create([
            'title' => trim($title),
            'slug' => $slug,
            'description' => $this->normalizeNullableText($description),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => (int) $file->getSize(),
        ]);
    }

    private function makeUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'download';
        $slug = $baseSlug;
        $suffix = 2;

        while (DownloadAsset::query()->where('slug', $slug)->exists()) {
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

    private function normalizeNullableText(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}