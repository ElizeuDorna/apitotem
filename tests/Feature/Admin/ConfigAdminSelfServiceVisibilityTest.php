<?php

namespace Tests\Feature\Admin;

use App\Models\Configuracao;
use App\Models\User;
use App\Models\WebScreenModel;
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
            'configSection' => 'cadastro-login',
            'showSelfServiceRegisterOnLogin' => '0',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('configuracoes', [
            'empresa_id' => null,
            'showSelfServiceRegisterOnLogin' => false,
        ]);
    }

    public function test_default_admin_can_define_default_permissions_for_self_service_users(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.configadmin.update'), [
            'configSection' => 'cadastro-login',
            'showSelfServiceRegisterOnLogin' => '1',
            'selfServiceDefaultMenuPermissions' => [
                User::MENU_PRODUTOS,
                User::MENU_GRUPOS,
                User::MENU_TOKEN_API,
            ],
        ]);

        $response->assertRedirect();

        $config = Configuracao::query()->whereNull('empresa_id')->firstOrFail();

        $this->assertSame([
            User::MENU_PRODUTOS,
            User::MENU_GRUPOS,
            User::MENU_TOKEN_API,
        ], $config->selfServiceDefaultMenuPermissions);
    }

    public function test_saving_login_and_signup_settings_does_not_clear_asaas_credentials(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        Configuracao::query()->create([
            'empresa_id' => null,
            'asaasBaseUrl' => 'https://api.asaas.com/v3',
            'asaasApiKey' => 'asaas-key-123',
            'asaasWebhookToken' => 'webhook-token-123',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.configadmin.update'), [
            'configSection' => 'cadastro-login',
            'showSelfServiceRegisterOnLogin' => '1',
            'selfServiceDefaultMenuPermissions' => [
                User::MENU_PRODUTOS,
                User::MENU_GRUPOS,
            ],
        ]);

        $response->assertRedirect();

        $config = Configuracao::query()->whereNull('empresa_id')->firstOrFail();

        $this->assertSame('https://api.asaas.com/v3', $config->asaasBaseUrl);
        $this->assertSame('asaas-key-123', $config->asaasApiKey);
        $this->assertSame('webhook-token-123', $config->asaasWebhookToken);
    }

    public function test_saving_asaas_settings_does_not_clear_self_service_permissions(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        Configuracao::query()->create([
            'empresa_id' => null,
            'showSelfServiceRegisterOnLogin' => true,
            'selfServiceDefaultMenuPermissions' => [
                User::MENU_PRODUTOS,
                User::MENU_FINANCEIRO,
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.configadmin.update'), [
            'configSection' => 'asaas',
            'asaasBaseUrl' => 'https://sandbox.asaas.com/api/v3',
            'asaasApiKey' => 'new-asaas-key',
            'asaasWebhookToken' => 'new-webhook-token',
        ]);

        $response->assertRedirect();

        $config = Configuracao::query()->whereNull('empresa_id')->firstOrFail();

        $this->assertTrue((bool) $config->showSelfServiceRegisterOnLogin);
        $this->assertSame([
            User::MENU_PRODUTOS,
            User::MENU_FINANCEIRO,
        ], $config->selfServiceDefaultMenuPermissions);
    }

    public function test_default_admin_can_define_default_web_screen_model_for_self_service_users(): void
    {
        $admin = User::factory()->create([
            'email' => User::DEFAULT_ADMIN_EMAIL,
            'cpf' => User::DEFAULT_ADMIN_DOCUMENT,
        ]);

        $model = WebScreenModel::query()->create([
            'empresa_id' => null,
            'nome' => 'Modelo Auto Cadastro',
            'is_admin_default' => true,
            'config_payload' => [
                'showTitle' => false,
                'titleText' => 'Modelo Auto Cadastro',
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.configadmin.update'), [
            'configSection' => 'cadastro-login',
            'showSelfServiceRegisterOnLogin' => '1',
            'selfServiceDefaultMenuPermissions' => [
                User::MENU_PRODUTOS,
            ],
            'selfServiceDefaultWebScreenModelId' => (string) $model->id,
        ]);

        $response->assertRedirect();

        $config = Configuracao::query()->whereNull('empresa_id')->firstOrFail();

        $this->assertSame((int) $model->id, (int) $config->selfServiceDefaultWebScreenModelId);
    }
}