<?php

namespace Tests\Feature\Api;

use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaDocumentoSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_busca_empresa_por_cnpj_formatado(): void
    {
        $empresa = Empresa::query()->create([
            'codigo' => '1001',
            'nome' => 'Mercado Busca',
            'fantasia' => 'Mercado Busca',
            'razaosocial' => 'Mercado Busca LTDA',
            'cnpj_cpf' => '11222333000181',
            'urlimagem' => '',
            'email' => 'busca@example.com',
            'fone' => '11999999999',
        ]);

        $response = $this->getJson('/api/empresas/busca?documento=11.222.333/0001-81');

        $response
            ->assertOk()
            ->assertJson([
                'sucesso' => true,
                'mensagem' => 'Empresa encontrada com sucesso',
                'dados' => [
                    'id' => $empresa->id,
                    'cnpj_cpf' => '11222333000181',
                    'nome' => 'Mercado Busca',
                ],
            ]);
    }

    public function test_retorna_404_quando_empresa_nao_existe_para_documento(): void
    {
        $response = $this->getJson('/api/empresas/busca?documento=11.222.333/0001-81');

        $response
            ->assertNotFound()
            ->assertJson([
                'sucesso' => false,
                'mensagem' => 'Empresa nao encontrada para o documento informado.',
                'dados' => null,
            ]);
    }

    public function test_retorna_422_quando_documento_e_invalido(): void
    {
        $response = $this->getJson('/api/empresas/busca?documento=123');

        $response
            ->assertStatus(422)
            ->assertJson([
                'sucesso' => false,
                'mensagem' => 'Documento invalido.',
            ]);
    }
}