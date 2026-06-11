<?php

namespace Tests\Feature\Admin;

use App\Models\Configuracao;
use App\Models\Empresa;
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
            'selfServiceCloneDefaultWebScreenModel' => '1',
        ]);

        $response->assertRedirect();

        $config = Configuracao::query()->whereNull('empresa_id')->firstOrFail();

        $this->assertSame((int) $model->id, (int) $config->selfServiceDefaultWebScreenModelId);
        $this->assertTrue((bool) $config->selfServiceCloneDefaultWebScreenModel);
    }

    public function test_final_client_user_cannot_see_or_update_asaas_settings_in_config_admin(): void
    {
        $empresa = Empresa::query()->create([
            'nome' => 'Cliente Final Config Admin',
            'fantasia' => 'Cliente Final Config Admin',
            'razaosocial' => 'Cliente Final Config Admin LTDA',
            'cnpj_cpf' => '83198688000193',
            'email' => 'cliente-final-config-admin@example.com',
            'fone' => '11999990001',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'urlimagem' => '',
        ]);

        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'menu_permissions' => [User::MENU_CONFIG_ADMIN],
        ]);

        $this->actingAs($user)
            ->get(route('admin.configadmin.edit'))
            ->assertOk()
            ->assertDontSee('Salvar integração Asaas');

        $this->actingAs($user)
            ->post(route('admin.configadmin.update'), [
                'configSection' => 'asaas',
                'asaasBaseUrl' => 'https://api.asaas.com/v3',
                'asaasApiKey' => 'client-key',
                'asaasWebhookToken' => 'client-token',
            ])
            ->assertForbidden();
    }

    public function test_revenda_user_without_asaas_permission_cannot_see_or_update_asaas_settings(): void
    {
        $revenda = Empresa::query()->create([
            'nome' => 'Revenda Sem Asaas',
            'fantasia' => 'Revenda Sem Asaas',
            'razaosocial' => 'Revenda Sem Asaas LTDA',
            'cnpj_cpf' => '19633419000105',
            'email' => 'revenda-sem-asaas@example.com',
            'fone' => '11999990002',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'urlimagem' => '',
        ]);

        $clienteDaRevenda = Empresa::query()->create([
            'nome' => 'Cliente da Revenda Sem Asaas',
            'fantasia' => 'Cliente da Revenda Sem Asaas',
            'razaosocial' => 'Cliente da Revenda Sem Asaas LTDA',
            'cnpj_cpf' => '95787886000100',
            'email' => 'cliente-revenda-sem-asaas@example.com',
            'fone' => '11999990012',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => $revenda->id,
            'urlimagem' => '',
        ]);

        $user = User::factory()->create([
            'empresa_id' => $revenda->id,
            'menu_permissions' => [User::MENU_CONFIG_ADMIN],
        ]);

        $this->withSession(['revenda.empresa_ativa_id' => $clienteDaRevenda->id])
            ->actingAs($user)
            ->get(route('admin.configadmin.edit'))
            ->assertOk()
            ->assertDontSee('Salvar integração Asaas');

        $this->withSession(['revenda.empresa_ativa_id' => $clienteDaRevenda->id])
            ->actingAs($user)
            ->post(route('admin.configadmin.update'), [
                'configSection' => 'asaas',
                'asaasBaseUrl' => 'https://api.asaas.com/v3',
                'asaasApiKey' => 'revenda-key',
                'asaasWebhookToken' => 'revenda-token',
            ])
            ->assertForbidden();
    }

    public function test_revenda_user_with_asaas_permission_can_see_and_update_asaas_settings(): void
    {
        $revenda = Empresa::query()->create([
            'nome' => 'Revenda Com Asaas',
            'fantasia' => 'Revenda Com Asaas',
            'razaosocial' => 'Revenda Com Asaas LTDA',
            'cnpj_cpf' => '38291055000110',
            'email' => 'revenda-com-asaas@example.com',
            'fone' => '11999990003',
            'nivel_acesso' => Empresa::NIVEL_REVENDA,
            'urlimagem' => '',
        ]);

        $clienteDaRevenda = Empresa::query()->create([
            'nome' => 'Cliente da Revenda Com Asaas',
            'fantasia' => 'Cliente da Revenda Com Asaas',
            'razaosocial' => 'Cliente da Revenda Com Asaas LTDA',
            'cnpj_cpf' => '25817461000122',
            'email' => 'cliente-revenda-com-asaas@example.com',
            'fone' => '11999990013',
            'nivel_acesso' => Empresa::NIVEL_CLIENTE_FINAL,
            'revenda_id' => $revenda->id,
            'urlimagem' => '',
        ]);

        $user = User::factory()->create([
            'empresa_id' => $revenda->id,
            'menu_permissions' => [User::MENU_CONFIG_ADMIN, User::MENU_CONFIG_ADMIN_ASAAS],
        ]);

        $this->withSession(['revenda.empresa_ativa_id' => $clienteDaRevenda->id])
            ->actingAs($user)
            ->get(route('admin.configadmin.edit'))
            ->assertOk()
            ->assertSee('Integracao Asaas');

        $this->withSession(['revenda.empresa_ativa_id' => $clienteDaRevenda->id])
            ->actingAs($user)
            ->post(route('admin.configadmin.update'), [
                'configSection' => 'asaas',
                'asaasBaseUrl' => 'https://api.asaas.com/v3',
                'asaasApiKey' => 'revenda-key',
                'asaasWebhookToken' => 'revenda-token',
            ])
            ->assertRedirect();
    }
}