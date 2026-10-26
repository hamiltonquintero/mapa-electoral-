<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Campana;
use App\Models\Territorio;
use App\Models\Simpatizante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de autorización territorial y CRUD de simpatizantes.
 * 
 * Criterio de aceptación crítico (especificación §12.1):
 * Un usuario con rol de coordinador municipal NO puede ver ni por la
 * interfaz ni por la API registros de otro municipio.
 */
class SimpatizanteTest extends TestCase
{
    use RefreshDatabase;

    private User      $admin;
    private User      $digitador;
    private User      $coordinador;
    private Campana   $campana;
    private Territorio $territorio;
    private Territorio $otroTerritorio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->campana        = Campana::factory()->create(['activa' => true]);
        $this->territorio     = Territorio::factory()->municipio()->create();
        $this->otroTerritorio = Territorio::factory()->municipio()->create();

        // Superadministrador — acceso total
        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('Superadministrador');

        // Digitador — solo puede crear, no listar globalmente
        $this->digitador = User::factory()->create(['activo' => true]);
        $this->digitador->assignRole('Digitador');
        $this->digitador->campanas()->attach($this->campana);

        // Coordinador — solo ve su municipio
        $this->coordinador = User::factory()->create(['activo' => true]);
        $this->coordinador->assignRole('Coordinador Municipal');
        $this->coordinador->campanas()->attach($this->campana);
        $this->coordinador->territorios()->attach($this->territorio);
    }

    // ── Autenticación ─────────────────────────────────────────

    public function test_listar_simpatizantes_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/simpatizantes')
             ->assertStatus(401);
    }

    public function test_detalle_simpatizante_requiere_autenticacion(): void
    {
        $s = Simpatizante::factory()
                ->enCampana($this->campana)
                ->enTerritorio($this->territorio)
                ->create();

        $this->getJson("/api/v1/simpatizantes/{$s->id}")
             ->assertStatus(401);
    }

    // ── Autorización territorial (criterio §12.1) ─────────────

    /** El coordinador NO puede ver simpatizantes de otro municipio */
    public function test_coordinador_no_puede_ver_simpatizante_de_otro_municipio(): void
    {
        $simpatizante = Simpatizante::factory()
            ->enCampana($this->campana)
            ->enTerritorio($this->otroTerritorio) // territorio diferente al asignado
            ->create();

        $this->actingAs($this->coordinador, 'sanctum')
             ->getJson("/api/v1/simpatizantes/{$simpatizante->id}")
             ->assertStatus(403);
    }

    /** El coordinador SÍ puede ver simpatizantes de su municipio */
    public function test_coordinador_puede_ver_simpatizante_de_su_municipio(): void
    {
        $simpatizante = Simpatizante::factory()
            ->enCampana($this->campana)
            ->enTerritorio($this->territorio)
            ->create();

        $this->actingAs($this->coordinador, 'sanctum')
             ->getJson("/api/v1/simpatizantes/{$simpatizante->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', (string) $simpatizante->id);
    }

    /** El superadmin puede ver cualquier simpatizante */
    public function test_superadmin_puede_ver_cualquier_simpatizante(): void
    {
        $simpatizante = Simpatizante::factory()
            ->enCampana($this->campana)
            ->enTerritorio($this->otroTerritorio)
            ->create();

        $this->actingAs($this->admin, 'sanctum')
             ->getJson("/api/v1/simpatizantes/{$simpatizante->id}")
             ->assertStatus(200);
    }

    // ── Listado con scope territorial ────────────────────────

    /** El listado del coordinador solo muestra registros de su territorio */
    public function test_listado_solo_muestra_registros_del_territorio_asignado(): void
    {
        // 3 en su territorio, 2 en otro
        Simpatizante::factory(3)->enCampana($this->campana)->enTerritorio($this->territorio)->create();
        Simpatizante::factory(2)->enCampana($this->campana)->enTerritorio($this->otroTerritorio)->create();

        $response = $this->actingAs($this->coordinador, 'sanctum')
             ->getJson('/api/v1/simpatizantes');

        $response->assertStatus(200);
        $data = $response->json('data');
        // Solo deben aparecer los 3 de su territorio
        $this->assertCount(3, $data);
    }

    // ── Permisos ─────────────────────────────────────────────

    /** Digitador NO puede eliminar */
    public function test_digitador_no_puede_eliminar(): void
    {
        $simpatizante = Simpatizante::factory()
            ->enCampana($this->campana)
            ->enTerritorio($this->territorio)
            ->create();

        $this->actingAs($this->digitador, 'sanctum')
             ->deleteJson("/api/v1/simpatizantes/{$simpatizante->id}")
             ->assertStatus(403);
    }

    /** Digitador NO puede exportar */
    public function test_digitador_no_puede_exportar(): void
    {
        $this->actingAs($this->digitador, 'sanctum')
             ->postJson('/api/v1/exportaciones', [
                 'motivo' => 'Test exportacion',
             ])
             ->assertStatus(403);
    }

    // ── No duplicados (regla de negocio §10.2) ────────────────

    /** No se pueden crear dos simpatizantes con la misma cédula en la misma campaña */
    public function test_no_permite_cedula_duplicada_en_misma_campana(): void
    {
        $cedula = '12345678901';
        $hashKey = config('encryption.cedula_hash_key', 'test-key');

        // Insertar directamente para evitar dependencia del cifrado
        Simpatizante::factory()->enCampana($this->campana)->enTerritorio($this->territorio)->create([
            'cedula_hash' => hash_hmac('sha256', $cedula, $hashKey),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/simpatizantes/buscar-duplicados', [
                'cedula'     => $cedula,
                'campana_id' => $this->campana->id,
            ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    // ── Acciones de cambio de estado ─────────────────────────

    public function test_cambiar_estado_a_validado(): void
    {
        $simpatizante = Simpatizante::factory()
            ->enCampana($this->campana)
            ->enTerritorio($this->territorio)
            ->create(['estado' => 'borrador']);

        $this->actingAs($this->admin, 'sanctum')
             ->postJson("/api/v1/simpatizantes/{$simpatizante->id}/cambiar-estado", [
                 'estado' => 'validado',
             ])
             ->assertStatus(200);

        $this->assertDatabaseHas('simpatizantes', [
            'id'     => $simpatizante->id,
            'estado' => 'validado',
        ]);
    }
}
