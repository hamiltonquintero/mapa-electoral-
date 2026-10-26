<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Campana;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportacionTest extends TestCase
{
    use RefreshDatabase;

    private User   $admin;
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

    public function test_descargar_plantilla_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/importaciones/plantilla')
             ->assertStatus(401);
    }

    public function test_descargar_plantilla_csv(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
             ->get('/api/v1/importaciones/plantilla');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_validar_archivo_invalido_retorna_errores(): void
    {
        $contenidoCsv = implode("\n", [
            'nombres,apellidos,cedula',        // header correcto
            'Juan,Pérez,',                     // cédula vacía
            ',López,12345678',                 // nombre vacío
        ]);

        $archivo = UploadedFile::fake()->createWithContent(
            'simpatizantes.csv',
            $contenidoCsv,
        );

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/importaciones/validar', [
                'archivo' => $archivo,
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThan(0, $data['filas_error'] ?? 0);
    }

    public function test_listado_importaciones_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/importaciones')
             ->assertStatus(401);
    }

    public function test_digitador_no_puede_revertir_importacion(): void
    {
        $digitador = User::factory()->create(['activo' => true]);
        $digitador->assignRole('Digitador');
        $digitador->campanas()->attach($this->campana);

        $this->actingAs($digitador, 'sanctum')
             ->postJson('/api/v1/importaciones/1/revertir')
             ->assertStatus(403);
    }
}
