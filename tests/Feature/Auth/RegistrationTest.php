<?php

namespace Tests\Feature\Auth;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'cpf' => '12345678901',
        ]);

        $response = $this->actingAs($user)->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $authUser = User::factory()->create([
            'cpf' => '12345678901',
        ]);

        $empresa = Empresa::query()->create([
            'cnpj_cpf' => '12345678000195',
            'nome' => 'Empresa Teste',
            'fantasia' => 'Empresa Teste',
            'razaosocial' => 'Empresa Teste LTDA',
            'urlimagem' => 'logo.png',
        ]);

        $response = $this->actingAs($authUser)->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'cpf' => '98765432100',
            'empresa_id' => $empresa->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticatedAs($authUser);
        $response->assertRedirect(route('register', absolute: false));
    }
}
