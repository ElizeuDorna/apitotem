<?php

namespace Tests\Feature\Auth;

use App\Models\Configuracao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Criar conta da empresa com 7 dias de trial');
    }

    public function test_login_screen_hides_self_service_link_when_disabled_in_admin_config(): void
    {
        Configuracao::query()->create([
            'empresa_id' => null,
            'showSelfServiceRegisterOnLogin' => false,
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Criar conta da empresa com 7 dias de trial');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'cpf' => '12345678901',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'cpf' => $user->cpf,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'cpf' => '12345678901',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'cpf' => $user->cpf,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
