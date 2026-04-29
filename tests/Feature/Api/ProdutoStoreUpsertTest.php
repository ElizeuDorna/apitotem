<?php

namespace Tests\Feature\Api;

use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoStoreUpsertTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_produtos_cria_quando_codigo_nao_existe(): void
    {
        $empresa = $this->createEmpresa('token-produto-create');
        [$departamento, $grupo] = $this->createRelacionamentos($empresa, 'Bebidas', 'Refrigerantes');

        $response = $this->withToken('token-produto-create')->postJson('/api/produtos', [
            'CODIGO' => '78901',
            'NOME' => 'Coca-Cola 2L',
            'PRECO' => 9.99,
            'OFERTA' => 8.99,
            'IMG' => 'https://example.com/coca.jpg',
            'departamento_id' => $departamento->id,
            'grupo_id' => $grupo->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('sucesso', true)
            ->assertJsonPath('mensagem', 'Produto cadastrado com sucesso')
            ->assertJsonPath('dados.codigo', '78901');

        $this->assertDatabaseHas('produto', [
            'empresa_id' => $empresa->id,
            'CODIGO' => '78901',
            'NOME' => 'Coca-Cola 2L',
        ]);
    }

    public function test_post_produtos_atualiza_quando_codigo_ja_existe_na_mesma_empresa(): void
    {
        $empresa = $this->createEmpresa('token-produto-update');
        [$departamentoA, $grupoA] = $this->createRelacionamentos($empresa, 'Bebidas', 'Refrigerantes');
        [$departamentoB, $grupoB] = $this->createRelacionamentos($empresa, 'Mercearia', 'Promoções');

        $produto = Produto::query()->create([
            'CODIGO' => '78901',
            'NOME' => 'Coca-Cola 2L',
            'cnpj_cpf' => $empresa->cnpj_cpf,
            'empresa_id' => $empresa->id,
            'PRECO' => 9.99,
            'OFERTA' => 8.99,
            'IMG' => 'https://example.com/original.jpg',
            'departamento_id' => $departamentoA->id,
            'grupo_id' => $grupoA->id,
        ]);

        $response = $this->withToken('token-produto-update')->postJson('/api/produtos', [
            'CODIGO' => '78901',
            'NOME' => 'Coca-Cola 2L Zero',
            'PRECO' => 10.49,
            'OFERTA' => 9.49,
            'IMG' => 'https://example.com/atualizado.jpg',
            'departamento_id' => $departamentoB->id,
            'grupo_id' => $grupoB->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('sucesso', true)
            ->assertJsonPath('mensagem', 'Produto atualizado com sucesso')
            ->assertJsonPath('dados.codigo', '78901')
            ->assertJsonPath('dados.nome', 'Coca-Cola 2L Zero');

        $this->assertDatabaseCount('produto', 1);

        $produto->refresh();

        $this->assertSame('Coca-Cola 2L Zero', $produto->NOME);
        $this->assertSame('10.49', $produto->PRECO);
        $this->assertSame('9.49', $produto->OFERTA);
        $this->assertSame('https://example.com/atualizado.jpg', $produto->IMG);
        $this->assertSame($departamentoB->id, $produto->departamento_id);
        $this->assertSame($grupoB->id, $produto->grupo_id);
    }

    private function createEmpresa(string $token): Empresa
    {
        return Empresa::query()->create([
            'codigo' => substr(str_replace('-', '', uniqid('', true)), 0, 12),
            'nome' => 'Empresa Produto',
            'fantasia' => 'Empresa Produto',
            'razaosocial' => 'Empresa Produto LTDA',
            'cnpj_cpf' => '11111111000111',
            'urlimagem' => '',
            'email' => uniqid('produto_', true).'@example.com',
            'fone' => '11999999999',
            'api_token' => $token,
        ]);
    }

    private function createRelacionamentos(Empresa $empresa, string $departamentoNome, string $grupoNome): array
    {
        $departamento = Departamento::query()->create([
            'nome' => $departamentoNome,
            'empresa_id' => $empresa->id,
        ]);

        $grupo = Grupo::query()->create([
            'nome' => $grupoNome,
            'departamento_id' => $departamento->id,
            'empresa_id' => $empresa->id,
        ]);

        return [$departamento, $grupo];
    }
}