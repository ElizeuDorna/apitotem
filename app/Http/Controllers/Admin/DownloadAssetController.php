<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadAsset;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadAssetController extends Controller
{
    private const STORAGE_DISK = 'public';

    public function index(): View
    {
        $user = Auth::user();

        abort_unless($user?->hasMenuAccess(User::MENU_DOWNLOADS), 403);

        return view('admin.downloads.index', [
            'isDefaultAdmin' => (bool) $user?->isDefaultAdmin(),
        ]);
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
}