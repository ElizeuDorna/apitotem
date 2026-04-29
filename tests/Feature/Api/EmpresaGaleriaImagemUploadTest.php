<?php

namespace Tests\Feature\Api;

use App\Models\Empresa;
use App\Models\GaleriaNova;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmpresaGaleriaImagemUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_imagem_para_galeria_da_empresa_e_retorna_url_publica(): void
    {
        Storage::fake('images');

        $empresa = Empresa::query()->create([
            'codigo' => '3001',
            'nome' => 'Empresa Galeria',
            'fantasia' => 'Empresa Galeria',
            'razaosocial' => 'Empresa Galeria LTDA',
            'cnpj_cpf' => '99888777000166',
            'urlimagem' => '',
            'email' => 'galeria@example.com',
            'fone' => '11999999999',
            'api_token' => 'token-galeria',
        ]);

        $response = $this->withToken('token-galeria')->post('/api/galeria-imagem/upload', [
            'name' => 'Banner API',
            'image' => UploadedFile::fake()->image('banner.png', 600, 600),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('sucesso', true)
            ->assertJsonPath('criado', true)
            ->assertJsonPath('dados.empresa_id', $empresa->id)
            ->assertJsonPath('dados.name', 'Banner API');

        $gallery = GaleriaNova::query()->firstOrFail();

        $this->assertSame($empresa->id, $gallery->empresa_id);
        $this->assertStringStartsWith('/storage-images/empresas/99888777000166/galeria/', (string) $response->json('dados.url'));
        Storage::disk('images')->assertExists((string) $gallery->file_path);
    }

    public function test_reutiliza_upload_duplicado_na_mesma_empresa(): void
    {
        Storage::fake('images');

        Empresa::query()->create([
            'codigo' => '3002',
            'nome' => 'Empresa Galeria 2',
            'fantasia' => 'Empresa Galeria 2',
            'razaosocial' => 'Empresa Galeria 2 LTDA',
            'cnpj_cpf' => '88777666000155',
            'urlimagem' => '',
            'email' => 'galeria2@example.com',
            'fone' => '11999999999',
            'api_token' => 'token-galeria-2',
        ]);

        $this->withToken('token-galeria-2')->post('/api/galeria-imagem/upload', [
            'name' => 'Banner Repetido',
            'image' => UploadedFile::fake()->createWithContent('banner.png', 'conteudo-identico'),
        ])->assertCreated();

        $response = $this->withToken('token-galeria-2')->post('/api/galeria-imagem/upload', [
            'name' => 'Banner Repetido 2',
            'image' => UploadedFile::fake()->createWithContent('banner-copia.png', 'conteudo-identico'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('sucesso', true)
            ->assertJsonPath('criado', false);

        $this->assertDatabaseCount('galeria_novas', 1);
    }
}