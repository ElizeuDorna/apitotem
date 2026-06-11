<?php

namespace Tests\Feature\Admin;

use App\Models\Configuracao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigAdminSelfServiceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_can_disable_self_service_link_on_login(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.configadmin.update'), [
            'showSelfServiceRegisterOnLogin' => '0',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('configuracoes', [
            'empresa_id' => null,
            'showSelfServiceRegisterOnLogin' => false,
        ]);
    }
}