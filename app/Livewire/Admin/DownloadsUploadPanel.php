<?php

namespace App\Livewire\Admin;

use App\Models\DownloadAsset;
use App\Models\User;
use App\Services\DownloadAssetService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Livewire\Component;
use Livewire\WithFileUploads;

class DownloadsUploadPanel extends Component
{
    use WithFileUploads;

    public ?int $editingDownloadId = null;

    public string $title = '';

    public string $description = '';

    public $file;

    public ?string $statusMessage = null;

    public int $uploadIteration = 0;

    public function save(DownloadAssetService $downloadAssetService): void
    {
        abort_unless(Auth::user()?->canManageDownloads(), 403);

        $editingDownload = $this->editingDownloadId
            ? DownloadAsset::query()->findOrFail($this->editingDownloadId)
            : null;

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:160', Rule::unique('download_assets', 'title')->ignore($editingDownload?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [($editingDownload ? 'nullable' : 'required'), File::default()->max(262144)],
        ], [
            'title.required' => 'Informe o nome do arquivo para exibição.',
            'file.required' => 'Selecione um arquivo para upload.',
            'file.max' => 'O arquivo não pode ultrapassar 256 MB.',
        ]);

        if ($editingDownload) {
            $downloadAssetService->update(
                $editingDownload,
                (string) $validated['title'],
                $validated['description'] ?? null,
                $validated['file'] ?? null
            );

            $this->statusMessage = 'Arquivo de download atualizado com sucesso.';
        } else {
            $downloadAssetService->create(
                (string) $validated['title'],
                $validated['description'] ?? null,
                $validated['file']
            );

            $this->statusMessage = 'Arquivo de download criado com sucesso.';
        }

        $this->resetForm();
        $this->resetErrorBag('delete');
    }

    public function editDownload(int $downloadId): void
    {
        abort_unless(Auth::user()?->canManageDownloads(), 403);

        $download = DownloadAsset::query()->findOrFail($downloadId);

        $this->editingDownloadId = $download->id;
        $this->title = (string) $download->title;
        $this->description = (string) ($download->description ?? '');
        $this->file = null;
        $this->statusMessage = null;
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        abort_unless(Auth::user()?->canManageDownloads(), 403);

        $this->resetForm();
        $this->statusMessage = null;
    }

    public function deleteDownload(int $downloadId, DownloadAssetService $downloadAssetService): void
    {
        abort_unless(Auth::user()?->canManageDownloads(), 403);

        $download = DownloadAsset::query()->findOrFail($downloadId);
        $name = $download->title;

        $downloadAssetService->delete($download);

        if ($this->editingDownloadId === $downloadId) {
            $this->resetForm();
        }

        $this->resetErrorBag('delete');
        $this->statusMessage = "Arquivo de download {$name} removido com sucesso.";
    }

    public function render()
    {
        $user = Auth::user();

        abort_unless($user?->hasMenuAccess(User::MENU_DOWNLOADS), 403);

        return view('livewire.admin.downloads-upload-panel', [
            'downloads' => DownloadAsset::query()->ordered()->get(),
            'canManageDownloads' => (bool) $user?->canManageDownloads(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingDownloadId', 'title', 'description', 'file']);
        $this->uploadIteration++;
        $this->resetValidation();
    }
}