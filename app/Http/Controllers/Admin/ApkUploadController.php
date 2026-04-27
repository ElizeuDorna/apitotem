<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApkUploadController extends Controller
{
    private const APK_DISK = 'public';
    private const APK_PATH = 'apk/install.apk';

    public function index(): View
    {
        $this->authorizeDefaultAdmin();

        $apkExists = Storage::disk(self::APK_DISK)->exists(self::APK_PATH);
        $apkSizeBytes = $apkExists ? (int) Storage::disk(self::APK_DISK)->size(self::APK_PATH) : null;
        $apkLastModified = $apkExists ? (int) Storage::disk(self::APK_DISK)->lastModified(self::APK_PATH) : null;

        return view('admin.apk-upload.index', [
            'apkExists' => $apkExists,
            'apkSizeBytes' => $apkSizeBytes,
            'apkLastModified' => $apkLastModified,
            'apkDownloadUrl' => url('/install.apk'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        $validated = $request->validate([
            'apk_file' => ['required', File::types(['apk'])->max(262144)],
        ], [
            'apk_file.required' => 'Envie o arquivo APK.',
            'apk_file.max' => 'O APK nao pode ultrapassar 256 MB.',
        ]);

        $apkFile = $validated['apk_file'];
        Storage::disk(self::APK_DISK)->putFileAs('apk', $apkFile, 'install.apk');

        return redirect()
            ->route('admin.apk-upload.index')
            ->with('status', 'APK enviado com sucesso e publicado em /install.apk');
    }

    public function download(): BinaryFileResponse
    {
        abort_unless(Storage::disk(self::APK_DISK)->exists(self::APK_PATH), 404);

        return response()->download(
            Storage::disk(self::APK_DISK)->path(self::APK_PATH),
            'install.apk',
            ['Content-Type' => 'application/vnd.android.package-archive']
        );
    }

    private function authorizeDefaultAdmin(): void
    {
        abort_unless(Auth::user()?->isDefaultAdmin(), 403);
    }
}