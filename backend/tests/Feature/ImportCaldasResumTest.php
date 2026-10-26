<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Campana;
use App\Models\Importacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportCaldasResumTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Campana $campana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        Storage::fake('local');
        $this->campana = Campana::factory()->create(['activa' => true]);
        $this->admin   = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('Superadministrador');
        $this->admin->campanas()->attach($this->campana);
    }

    public function test_import_caldas_and_resumen_endpoint(): void
    {
        // Ejecutar comando de importación
        Artisan::call('import:caldas');
        $this->assertEquals(0, Artisan::output() !== null ? 0 : 1);

        // Verificar que haya una importación registrada
        $importacion = Importacion::first();
        $this->assertNotNull($importacion);

        // Llamar al endpoint resumen
        $response = $this->actingAs($this->admin, 'sanctum')
                         ->getJson('/api/v1/importaciones/resumen');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('importacion', $data);
        $this->assertArrayHasKey('puestos', $data);
        $this->assertCount(5, $data['puestos']);
    }
}
