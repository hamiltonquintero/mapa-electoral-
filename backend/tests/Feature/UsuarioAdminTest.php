<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $coordinador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->superadmin = User::factory()->create(['activo' => true]);
        $this->superadmin->assignRole('Superadministrador');

        $this->coordinador = User::factory()->create(['activo' => true]);
        $this->coordinador->assignRole('Coordinador Municipal');
    }

    public function test_superadmin_puede_listar_usuarios(): void
    {
        User::factory(5)->create();

        $this->actingAs($this->superadmin, 'sanctum')
             ->getJson('/api/v1/usuarios')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'pagination']);
    }

    public function test_coordinador_no_puede_listar_usuarios(): void
    {
        $this->actingAs($this->coordinador, 'sanctum')
             ->getJson('/api/v1/usuarios')
             ->assertStatus(403);
    }

    public function test_superadmin_puede_crear_usuario(): void
    {
        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->postJson('/api/v1/usuarios', [
                'name'                  => 'Nuevo Usuario',
                'email'                 => 'nuevo@example.com',
                'password'              => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'rol'                   => 'Digitador',
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Nuevo Usuario');

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_superadmin_puede_asignar_rol(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole('Digitador');

        $this->actingAs($this->superadmin, 'sanctum')
             ->postJson("/api/v1/usuarios/{$usuario->id}/rol", [
                 'rol' => 'Coordinador Municipal',
             ])
             ->assertStatus(200);

        $this->assertTrue($usuario->fresh()->hasRole('Coordinador Municipal'));
    }

    public function test_superadmin_no_puede_eliminarse_a_si_mismo(): void
    {
        $this->actingAs($this->superadmin, 'sanctum')
             ->deleteJson("/api/v1/usuarios/{$this->superadmin->id}")
             ->assertStatus(422);
    }

    public function test_crear_usuario_con_email_duplicado_falla(): void
    {
        $existente = User::factory()->create(['email' => 'existente@example.com']);

        $this->actingAs($this->superadmin, 'sanctum')
             ->postJson('/api/v1/usuarios', [
                 'name'                  => 'Otro Usuario',
                 'email'                 => 'existente@example.com',
                 'password'              => 'SecurePass123!',
                 'password_confirmation' => 'SecurePass123!',
                 'rol'                   => 'Digitador',
             ])
             ->assertStatus(422);
    }
}
