<?php

namespace Tests\Feature\Admin;

use App\Models\DownloadAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DownloadAssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_download_permission_can_view_admin_downloads_grid(): void
    {
        $user = User::factory()->create([
            'cpf' => '11122233344',
            'menu_permissions' => [User::MENU_DOWNLOADS],
        ]);

        $response = $this->actingAs($user)->get(route('admin.downloads.index'));

        $response
            ->assertOk()
            ->assertSee('Downloads');
    }

    public function test_user_without_download_permission_cannot_view_admin_downloads_grid(): void
    {
        $user = User::factory()->create([
            'cpf' => '55566677788',
            'menu_permissions' => [User::MENU_PRODUTOS],
        ]);

        $response = $this->actingAs($user)->get(route('admin.downloads.index'));

        $response->assertForbidden();
    }

    public function test_public_page_lists_and_downloads_registered_file(): void
    {
        $path = 'downloads/test-catalogo-publico.apk';
        Storage::disk('public')->put($path, 'conteudo-de-teste');

        $download = DownloadAsset::query()->create([
            'title' => 'Catálogo Android',
            'slug' => 'catalogo-android',
            'description' => 'Versão liberada para clientes.',
            'file_path' => $path,
            'original_name' => 'catalogo.apk',
            'mime_type' => 'application/vnd.android.package-archive',
            'size_bytes' => Storage::disk('public')->size($path),
        ]);

        $this->get(route('downloads.public.index'))
            ->assertOk()
            ->assertSee('Catálogo Android')
            ->assertSee(route('downloads.file', $download));

        $downloadResponse = $this->get(route('downloads.file', $download));

        $downloadResponse
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=catalogo.apk');
    }

    public function test_user_with_download_permission_can_download_from_admin_route(): void
    {
        $path = 'downloads/test-admin-catalogo.apk';
        Storage::disk('public')->put($path, 'conteudo-admin');

        $download = DownloadAsset::query()->create([
            'title' => 'Catálogo Admin',
            'slug' => 'catalogo-admin',
            'description' => null,
            'file_path' => $path,
            'original_name' => 'catalogo-admin.apk',
            'mime_type' => 'application/vnd.android.package-archive',
            'size_bytes' => Storage::disk('public')->size($path),
        ]);

        $user = User::factory()->create([
            'cpf' => '44455566677',
            'menu_permissions' => [User::MENU_DOWNLOADS],
        ]);

        $this->actingAs($user)
            ->get(route('admin.downloads.download', $download))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=catalogo-admin.apk');
    }

    public function test_default_admin_can_upload_download_asset_via_livewire_component(): void
    {
        Config::set('livewire.temporary_file_upload.disk', 'public');
        File::ensureDirectoryExists(storage_path('framework/testing/disks/tmp-for-tests'));

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Admin\DownloadsUploadPanel::class)
            ->set('title', 'Instalador Windows')
            ->set('description', 'Primeiro piloto com Livewire.')
            ->set('file', UploadedFile::fake()->create('instalador.zip', 64, 'application/zip'))
            ->call('save')
            ->assertSet('title', '')
            ->assertSet('description', '')
            ->assertSee('Arquivo de download criado com sucesso.');

        $download = DownloadAsset::query()->where('title', 'Instalador Windows')->firstOrFail();

        Storage::disk('public')->assertExists($download->file_path);
        $this->assertSame('Primeiro piloto com Livewire.', $download->description);
    }

    public function test_default_admin_delete_also_removes_file_from_storage(): void
    {
        $path = 'downloads/remover-arquivo.apk';
        Storage::disk('public')->put($path, 'conteudo-para-remover');

        $download = DownloadAsset::query()->create([
            'title' => 'Arquivo para Remover',
            'slug' => 'arquivo-para-remover',
            'description' => 'Teste de exclusao completa.',
            'file_path' => $path,
            'original_name' => 'remover-arquivo.apk',
            'mime_type' => 'application/vnd.android.package-archive',
            'size_bytes' => Storage::disk('public')->size($path),
        ]);

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Admin\DownloadsUploadPanel::class)
            ->call('deleteDownload', $download->id)
            ->assertSee('Arquivo de download Arquivo para Remover removido com sucesso.');

        $this->assertDatabaseMissing('download_assets', ['id' => $download->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_default_admin_can_edit_download_asset_via_livewire_component(): void
    {
        Config::set('livewire.temporary_file_upload.disk', 'public');
        File::ensureDirectoryExists(storage_path('framework/testing/disks/tmp-for-tests'));

        $download = DownloadAsset::query()->create([
            'title' => 'Arquivo Antigo',
            'slug' => 'arquivo-antigo',
            'description' => 'Descricao antiga.',
            'file_path' => 'downloads/arquivo-antigo.zip',
            'original_name' => 'arquivo-antigo.zip',
            'mime_type' => 'application/zip',
            'size_bytes' => 1024,
        ]);

        Storage::disk('public')->put($download->file_path, 'arquivo-antigo');

        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Admin\DownloadsUploadPanel::class)
            ->call('editDownload', $download->id)
            ->set('title', 'Arquivo Atualizado')
            ->set('description', 'Descricao nova.')
            ->set('file', UploadedFile::fake()->create('arquivo-novo.zip', 32, 'application/zip'))
            ->call('save')
            ->assertSet('editingDownloadId', null)
            ->assertSee('Arquivo de download atualizado com sucesso.');

        $download->refresh();

        $this->assertSame('Arquivo Atualizado', $download->title);
        $this->assertSame('Descricao nova.', $download->description);
        $this->assertNotSame('downloads/arquivo-antigo.zip', $download->file_path);
        Storage::disk('public')->assertMissing('downloads/arquivo-antigo.zip');
        Storage::disk('public')->assertExists($download->file_path);
    }
}