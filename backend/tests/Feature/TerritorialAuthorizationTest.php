<?php

namespace Tests\Feature;

use App\Models\Campana;
use App\Models\Simpatizante;
use App\Models\Territorio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TerritorialAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles necesarios
        Role::create(['name' => 'Superadministrador']);
        Role::create(['name' => 'Coordinador Municipal']);
        Role::create(['name' => 'Digitador']);
    }

    public function test_user_can_access_assigned_territory()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create(['tipo' => 'municipio']);
        
        $user->territorios()->attach($territorio->id);
        $user->campanas()->attach($campana->id);
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $accesibles = Simpatizante::accesiblePorUsuario($user)->pluck('id');
        
        $this->assertTrue($accesibles->contains($simpatizante->id));
    }

    public function test_user_can_access_descendant_territories()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        
        // Crear jerarquía: Municipio > Barrio
        $municipio = Territorio::factory()->create(['tipo' => 'municipio']);
        $barrio = Territorio::factory()->create([
            'tipo' => 'barrio',
            'padre_id' => $municipio->id,
        ]);
        
        // Usuario asignado solo al municipio
        $user->territorios()->attach($municipio->id);
        $user->campanas()->attach($campana->id);
        
        // Simpatizante en el barrio (descendiente)
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $barrio->id,
        ]);
        
        $territoriosAccesibles = $user->getAccessibleTerritoryIds();
        
        $this->assertContains($municipio->id, $territoriosAccesibles);
        $this->assertContains($barrio->id, $territoriosAccesibles);
        
        $accesibles = Simpatizante::accesiblePorUsuario($user)->pluck('id');
        $this->assertTrue($accesibles->contains($simpatizante->id));
    }

    public function test_user_cannot_access_unassigned_territory()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $territorioAsignado = Territorio::factory()->create();
        $territorioNoAsignado = Territorio::factory()->create();
        
        $user->territorios()->attach($territorioAsignado->id);
        $user->campanas()->attach($campana->id);
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorioNoAsignado->id,
        ]);
        
        $accesibles = Simpatizante::accesiblePorUsuario($user)->pluck('id');
        
        $this->assertFalse($accesibles->contains($simpatizante->id));
    }

    public function test_user_cannot_access_unassigned_campaign()
    {
        $user = User::factory()->create();
        $campanaAsignada = Campana::factory()->create();
        $campanaNoAsignada = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $user->territorios()->attach($territorio->id);
        $user->campanas()->attach($campanaAsignada->id);
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campanaNoAsignada->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $accesibles = Simpatizante::accesiblePorUsuario($user)->pluck('id');
        
        $this->assertFalse($accesibles->contains($simpatizante->id));
    }

    public function test_superadmin_can_access_all_data()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('Superadministrador');
        
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        // Superadmin NO tiene asignados territorios ni campañas
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $accesibles = Simpatizante::accesiblePorUsuario($superadmin)->pluck('id');
        
        // Debe tener acceso a pesar de no tener asignaciones
        $this->assertTrue($accesibles->contains($simpatizante->id));
    }

    public function test_user_without_territories_sees_nothing()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        // Usuario solo tiene campaña, pero NO territorios
        $user->campanas()->attach($campana->id);
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $accesibles = Simpatizante::accesiblePorUsuario($user)->pluck('id');
        
        $this->assertFalse($accesibles->contains($simpatizante->id));
        $this->assertCount(0, $accesibles);
    }

    public function test_user_without_campaigns_sees_nothing()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        // Usuario solo tiene territorio, pero NO campañas
        $user->territorios()->attach($territorio->id);
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $accesibles = Simpatizante::accesiblePorUsuario($user)->pluck('id');
        
        $this->assertFalse($accesibles->contains($simpatizante->id));
        $this->assertCount(0, $accesibles);
    }

    public function test_has_access_to_territorio_method()
    {
        $user = User::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $user->territorios()->attach($territorio->id);
        
        $this->assertTrue($user->hasAccessToTerritorio($territorio->id));
        
        $otroTerritorio = Territorio::factory()->create();
        $this->assertFalse($user->hasAccessToTerritorio($otroTerritorio->id));
    }

    public function test_has_access_to_campana_method()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        
        $user->campanas()->attach($campana->id);
        
        $this->assertTrue($user->hasAccessToCampana($campana->id));
        
        $otraCampana = Campana::factory()->create();
        $this->assertFalse($user->hasAccessToCampana($otraCampana->id));
    }

    public function test_get_accessible_territory_ids()
    {
        $user = User::factory()->create();
        $municipio = Territorio::factory()->create(['tipo' => 'municipio']);
        $barrio = Territorio::factory()->create([
            'tipo' => 'barrio',
            'padre_id' => $municipio->id,
        ]);
        
        $user->territorios()->attach($municipio->id);
        
        $territorios = $user->getAccessibleTerritoryIds();
        
        $this->assertIsArray($territorios);
        $this->assertContains($municipio->id, $territorios);
        $this->assertContains($barrio->id, $territorios);
    }

    public function test_get_accessible_campaign_ids()
    {
        $user = User::factory()->create();
        $campana1 = Campana::factory()->create();
        $campana2 = Campana::factory()->create();
        $campana3 = Campana::factory()->create();
        
        $user->campanas()->attach([$campana1->id, $campana2->id]);
        
        $campanas = $user->getAccessibleCampaignIds();
        
        $this->assertIsArray($campanas);
        $this->assertCount(2, $campanas);
        $this->assertContains($campana1->id, $campanas);
        $this->assertContains($campana2->id, $campanas);
        $this->assertNotContains($campana3->id, $campanas);
    }

    public function test_scope_applies_both_territory_and_campaign_filters()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $user->campanas()->attach($campana->id);
        $user->territorios()->attach($territorio->id);
        
        // Simpatizante válido (territorio y campaña correctos)
        $simpatizanteValido = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        // Simpatizante con campaña incorrecta
        $otraCampana = Campana::factory()->create();
        $simpatizanteCampanaIncorrecta = Simpatizante::factory()->create([
            'campana_id' => $otraCampana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        // Simpatizante con territorio incorrecto
        $otroTerritorio = Territorio::factory()->create();
        $simpatizanteTerritorioIncorrecto = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $otroTerritorio->id,
        ]);
        
        $accesibles = Simpatizante::accesiblePorUsuario($user)->pluck('id');
        
        $this->assertCount(1, $accesibles);
        $this->assertTrue($accesibles->contains($simpatizanteValido->id));
        $this->assertFalse($accesibles->contains($simpatizanteCampanaIncorrecta->id));
        $this->assertFalse($accesibles->contains($simpatizanteTerritorioIncorrecto->id));
    }

    public function test_territory_scope_middleware_adds_accessible_territories_to_request()
    {
        $user = User::factory()->create();
        $territorio = Territorio::factory()->create();
        $user->territorios()->attach($territorio->id);
        
        $this->actingAs($user, 'sanctum');
        
        $response = $this->getJson('/api/v1/simpatizantes');
        
        // Verificar que el middleware agregó los territorios al request
        $request = request();
        $this->assertNotNull($request->get('_accessible_territory_ids'));
        $this->assertIsArray($request->get('_accessible_territory_ids'));
    }

    public function test_campaign_scope_middleware_adds_accessible_campaigns_to_request()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $user->campanas()->attach($campana->id);
        
        $this->actingAs($user, 'sanctum');
        
        $response = $this->getJson('/api/v1/simpatizantes');
        
        // Verificar que el middleware agregó las campañas al request
        $request = request();
        $this->assertNotNull($request->get('_accessible_campaign_ids'));
        $this->assertIsArray($request->get('_accessible_campaign_ids'));
    }
}
