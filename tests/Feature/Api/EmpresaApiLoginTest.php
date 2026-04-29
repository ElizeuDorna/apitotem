<?php

namespace Tests\Feature\Api;

use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmpresaApiLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_realiza_login_com_senha_integracao_api(): void
    {
        Empresa::query()->create([
            'codigo' => '2001',
            'nome' => 'Mercado API',
            'fantasia' => 'Mercado API',
            'razaosocial' => 'Mercado API LTDA',
            'cnpj_cpf' => '11222333000181',
            'urlimagem' => '',
            'email' => 'api@example.com',
            'fone' => '11999999999',
            'senha_integracao_api' => Hash::make('senha-integracao'),
        ]);

        $response = $this->postJson('/api/login', [
            'cnpj_cpf' => '11222333000181',
            'senha' => 'senha-integracao',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('sucesso', true)
            ->assertJsonPath('empresa.nome', 'Mercado API');

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_cadastra_empresa_com_senha_integracao_api_opcional(): void
    {
        $response = $this->postJson('/api/empresas', [
            'nome' => 'Mercado Cadastro',
            'razaosocial' => 'Mercado Cadastro LTDA',
            'cnpj_cpf' => '19131243000197',
            'email' => 'cadastro@example.com',
            'fone' => '11999999999',
            'senha_integracao_api' => 'senha-nova',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('sucesso', true)
            ->assertJsonMissingPath('dados.senha_integracao_api');

        $empresa = Empresa::query()->where('cnpj_cpf', '19131243000197')->firstOrFail();

        $this->assertTrue(Hash::check('senha-nova', (string) $empresa->getAttribute('senha_integracao_api')));
    }
}