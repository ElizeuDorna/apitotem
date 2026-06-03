<?php

namespace Tests\Feature\Admin;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TotemWebLinkPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_screen_config_hides_totem_web_link_without_specific_permission(): void
    {
        $empresa = $this->createEmpresa();
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_CONFIG_TELA_WEB],
        ]);

        $this->actingAs($user)
            ->get(route('admin.web-screen-config.edit'))
            ->assertOk()
            ->assertDontSee('Abrir Totem Web');
    }

    public function test_web_screen_config_shows_totem_web_link_with_specific_permission(): void
    {
        $empresa = $this->createEmpresa();
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_CONFIG_TELA_WEB, User::MENU_ABRIR_TOTEM_WEB],
        ]);

        $this->actingAs($user)
            ->get(route('admin.web-screen-config.edit'))
            ->assertOk()
            ->assertSee('Abrir Totem Web');
    }

    public function test_organizar_lista_hides_totem_web_link_without_specific_permission(): void
    {
        $empresa = $this->createEmpresa();
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_ORGANIZAR_LISTA],
        ]);

        $this->actingAs($user)
            ->get(route('admin.organizar-lista.edit'))
            ->assertOk()
            ->assertDontSee('Abrir Totem Web');
    }

    public function test_organizar_lista_shows_totem_web_link_with_specific_permission(): void
    {
        $empresa = $this->createEmpresa();
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_ORGANIZAR_LISTA, User::MENU_ABRIR_TOTEM_WEB],
        ]);

        $this->actingAs($user)
            ->get(route('admin.organizar-lista.edit'))
            ->assertOk()
            ->assertSee('Abrir Totem Web');
    }

    private function createEmpresa(): Empresa
    {
        return Empresa::query()->create([
            'codigo' => '950',
            'nome' => 'Empresa Teste Link',
            'fantasia' => 'Empresa Teste Link',
            'razaosocial' => 'Empresa Teste Link LTDA',
            'cnpj_cpf' => fake()->numerify('########0001##'),
            'email' => fake()->unique()->safeEmail(),
            'fone' => '11999999999',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'api_token' => str_repeat('t', 60),
            'urlimagem' => '',
        ]);
    }
}