<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Campana;
use App\Models\Territorio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Campana $campana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->campana = Campana::factory()->create(['activa' => true]);
        $this->user    = User::factory()->create(['activo' => true]);
        $this->user->assignRole('Superadministrador');
    }

    public function test_endpoint_raiz_mapa_retorna_datos(): void
    {
        $this->actingAs($this->user, 'sanctum')
             ->getJson('/api/v1/mapa')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }

    public function test_buscar_en_mapa(): void
    {
        Territorio::factory()->create(['nombre' => 'Manizales', 'tipo' => 'municipio']);

        $this->actingAs($this->user, 'sanctum')
             ->getJson('/api/v1/mapa/buscar?q=Maniz')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }

    public function test_geojson_territorio_requiere_autenticacion(): void
    {
        $territorio = Territorio::factory()->create();

        $this->getJson("/api/v1/mapa/territorios/{$territorio->id}/geojson")
             ->assertStatus(401);
    }

    public function test_mapa_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/mapa')->assertStatus(401);
    }
}
