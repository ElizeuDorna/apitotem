<?php

namespace Tests\Feature\Api;

use App\Models\Departamento;
use App\Models\Empresa;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaDepartmentGroupUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_permite_departamento_duplicado_na_mesma_empresa(): void
    {
        $empresa = $this->createEmpresa('token-a');

        Departamento::query()->create([
            'nome' => 'Bebidas',
            'empresa_id' => $empresa->id,
        ]);

        $response = $this->withToken('token-a')->postJson('/api/departamentos', [
            'nome' => 'Bebidas',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    public function test_permite_departamento_mesmo_nome_em_empresas_diferentes(): void
    {
        $empresaA = $this->createEmpresa('token-a');
        $empresaB = $this->createEmpresa('token-b', '22222222000122', 'Empresa B');

        Departamento::query()->create([
            'nome' => 'Bebidas',
            'empresa_id' => $empresaA->id,
        ]);

        $response = $this->withToken('token-b')->postJson('/api/departamentos', [
            'nome' => 'Bebidas',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('dados.nome', 'Bebidas')
            ->assertJsonPath('dados.empresa_id', $empresaB->id);
    }

    public function test_nao_permite_grupo_duplicado_na_mesma_empresa(): void
    {
        $empresa = $this->createEmpresa('token-a');

        $departamentoA = Departamento::query()->create([
            'nome' => 'Bebidas',
            'empresa_id' => $empresa->id,
        ]);

        $departamentoB = Departamento::query()->create([
            'nome' => 'Mercearia',
            'empresa_id' => $empresa->id,
        ]);

        Grupo::query()->create([
            'nome' => 'Promoções',
            'departamento_id' => $departamentoA->id,
            'empresa_id' => $empresa->id,
        ]);

        $response = $this->withToken('token-a')->postJson('/api/grupos', [
            'nome' => 'Promoções',
            'departamento_id' => $departamentoB->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    public function test_permite_grupo_mesmo_nome_em_empresas_diferentes(): void
    {
        $empresaA = $this->createEmpresa('token-a');
        $empresaB = $this->createEmpresa('token-b', '22222222000122', 'Empresa B');

        $departamentoA = Departamento::query()->create([
            'nome' => 'Bebidas',
            'empresa_id' => $empresaA->id,
        ]);

        $departamentoB = Departamento::query()->create([
            'nome' => 'Bebidas',
            'empresa_id' => $empresaB->id,
        ]);

        Grupo::query()->create([
            'nome' => 'Promoções',
            'departamento_id' => $departamentoA->id,
            'empresa_id' => $empresaA->id,
        ]);

        $response = $this->withToken('token-b')->postJson('/api/grupos', [
            'nome' => 'Promoções',
            'departamento_id' => $departamentoB->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('dados.nome', 'Promoções')
            ->assertJsonPath('dados.empresa_id', $empresaB->id);
    }

    private function createEmpresa(string $token, string $cnpjCpf = '11111111000111', string $nome = 'Empresa A'): Empresa
    {
        return Empresa::query()->create([
            'codigo' => substr(str_replace('-', '', uniqid('', true)), 0, 12),
            'nome' => $nome,
            'fantasia' => $nome,
            'razaosocial' => $nome.' LTDA',
            'cnpj_cpf' => $cnpjCpf,
            'urlimagem' => '',
            'email' => strtolower(str_replace(' ', '', $nome)).uniqid().'@example.com',
            'fone' => '11999999999',
            'api_token' => $token,
        ]);
    }
}