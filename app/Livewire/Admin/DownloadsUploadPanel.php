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

    public string $title = '';

    public string $description = '';

    public $file;

    public ?string $statusMessage = null;

    public int $uploadIteration = 0;

    public function save(DownloadAssetService $downloadAssetService): void
    {
        abort_unless(Auth::user()?->isDefaultAdmin(), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:160', Rule::unique('download_assets', 'title')],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', File::default()->max(262144)],
        ], [
            'title.required' => 'Informe o nome do arquivo para exibição.',
            'file.required' => 'Selecione um arquivo para upload.',
            'file.max' => 'O arquivo não pode ultrapassar 256 MB.',
        ]);

        $downloadAssetService->create(
            (string) $validated['title'],
            $validated['description'] ?? null,
            $validated['file']
        );

        $this->reset(['title', 'description', 'file']);
        $this->uploadIteration++;
        $this->resetValidation();
        $this->statusMessage = 'Arquivo de download criado com sucesso.';
    }

    public function render()
    {
        $user = Auth::user();

        abort_unless($user?->hasMenuAccess(User::MENU_DOWNLOADS), 403);

        return view('livewire.admin.downloads-upload-panel', [
            'downloads' => DownloadAsset::query()->ordered()->get(),
            'isDefaultAdmin' => (bool) $user?->isDefaultAdmin(),
        ]);
    }
}