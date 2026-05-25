<?php

namespace Tests\Feature\Admin;

use App\Models\Configuracao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaIntegrationConfigAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_store_global_meta_configuration_from_config_admin(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.configadmin.update'), [
            'metaAppId' => '123456789012345',
            'metaRedirectUri' => 'https://painel.exemplo.com/admin/rede-social/instagram/callback',
            'produtoFormImagePreviewSize' => 48,
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('configuracoes', [
            'empresa_id' => null,
            'metaAppId' => '123456789012345',
            'metaRedirectUri' => 'https://painel.exemplo.com/admin/rede-social/instagram/callback',
        ]);
    }

    public function test_non_default_admin_cannot_store_global_meta_configuration(): void
    {
        $user = User::factory()->create([
            'cpf' => '98765432100',
            'menu_permissions' => [User::MENU_CONFIG_ADMIN],
        ]);

        Configuracao::query()->create([
            'empresa_id' => null,
            'metaAppId' => 'original-id',
            'metaRedirectUri' => 'https://original.exemplo.com/callback',
        ]);

        $response = $this->actingAs($user)->post(route('admin.configadmin.update'), [
            'metaAppId' => 'novo-id',
            'metaRedirectUri' => 'https://novo.exemplo.com/callback',
            'produtoFormImagePreviewSize' => 48,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('configuracoes', [
            'empresa_id' => null,
            'metaAppId' => 'original-id',
            'metaRedirectUri' => 'https://original.exemplo.com/callback',
        ]);
    }
}