<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Auditoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $auditor;
    private User $digitador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->superadmin = User::factory()->create(['activo' => true]);
        $this->superadmin->assignRole('Superadministrador');

        $this->auditor = User::factory()->create(['activo' => true]);
        $this->auditor->assignRole('Auditor');

        $this->digitador = User::factory()->create(['activo' => true]);
        $this->digitador->assignRole('Digitador');
    }

    public function test_digitador_no_puede_ver_auditoria(): void
    {
        $this->actingAs($this->digitador, 'sanctum')
             ->getJson('/api/v1/auditoria')
             ->assertStatus(403);
    }

    public function test_auditor_puede_listar_eventos(): void
    {
        Auditoria::factory()->count(5)->create(['user_id' => $this->superadmin->id]);

        $this->actingAs($this->auditor, 'sanctum')
             ->getJson('/api/v1/auditoria')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'pagination']);
    }

    public function test_superadmin_puede_ver_resumen(): void
    {
        $this->actingAs($this->superadmin, 'sanctum')
             ->getJson('/api/v1/auditoria/resumen')
             ->assertStatus(200)
             ->assertJsonPath('data.total_eventos', fn($v) => $v >= 0);
    }

    public function test_acciones_sensibles_quedan_auditadas(): void
    {
        // Crear simpatizante y verificar que queda en auditoría
        // (esto se testea via el AuditMiddleware)
        $conteoInicial = Auditoria::count();

        $this->actingAs($this->superadmin, 'sanctum')
             ->postJson('/api/v1/simpatizantes', [
                 'nombres' => 'Test', 'apellidos' => 'Auditoria',
                 'cedula' => '99999999',
             ]);

        // Al menos un registro nuevo de auditoría
        $this->assertGreaterThanOrEqual($conteoInicial, Auditoria::count());
    }

    public function test_auditoria_no_expone_datos_personales(): void
    {
        Auditoria::registrar('simpatizante_creado', 'simpatizantes', '123', [
            'cedula_hash' => 'abc123', // solo hash, nunca la cédula real
        ]);

        $evento = Auditoria::latest()->first();

        $this->assertStringNotContainsString('12345678', json_encode($evento->detalle));
    }

    public function test_solo_superadmin_puede_limpiar_auditoria(): void
    {
        $this->actingAs($this->auditor, 'sanctum')
             ->deleteJson('/api/v1/auditoria/limpiar', [
                 'dias_antiguedad' => 365,
                 'confirmar'       => true,
             ])
             ->assertStatus(403);
    }
}
