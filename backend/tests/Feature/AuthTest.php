<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_exitoso_retorna_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'activo'   => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    public function test_login_fallido_con_credenciales_incorrectas(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correcto')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'incorrecto',
        ])->assertStatus(422);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'activo'   => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ])->assertStatus(423);
    }

    public function test_me_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_me_retorna_usuario_autenticado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/v1/auth/me')
             ->assertStatus(200)
             ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_invalida_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/auth/logout')
             ->assertStatus(200);
    }
}
