<?php

namespace Tests\Unit;

use App\Models\Campana;
use App\Models\Simpatizante;
use App\Models\Territorio;
use App\Models\User;
use App\Traits\AppliesUserScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppliesUserScopeTest extends TestCase
{
    use RefreshDatabase, AppliesUserScope;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::create(['name' => 'Superadministrador']);
        Role::create(['name' => 'Digitador']);
    }

    public function test_apply_territory_scope_filters_by_assigned_territories()
    {
        $user = User::factory()->create();
        $territorio1 = Territorio::factory()->create();
        $territorio2 = Territorio::factory()->create();
        
        $user->territorios()->attach($territorio1->id);
        
        $campana = Campana::factory()->create();
        $user->campanas()->attach($campana->id);
        
        $simpatizante1 = Simpatizante::factory()->create([
            'territorio_id' => $territorio1->id,
            'campana_id' => $campana->id,
        ]);
        $simpatizante2 = Simpatizante::factory()->create([
            'territorio_id' => $territorio2->id,
            'campana_id' => $campana->id,
        ]);
        
        $query = $this->applyTerritoryScope(Simpatizante::query(), $user);
        $results = $query->pluck('id');
        
        $this->assertTrue($results->contains($simpatizante1->id));
        $this->assertFalse($results->contains($simpatizante2->id));
    }

    public function test_apply_campaign_scope_filters_by_assigned_campaigns()
    {
        $user = User::factory()->create();
        $campana1 = Campana::factory()->create();
        $campana2 = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $user->campanas()->attach($campana1->id);
        $user->territorios()->attach($territorio->id);
        
        $simpatizante1 = Simpatizante::factory()->create([
            'campana_id' => $campana1->id,
            'territorio_id' => $territorio->id,
        ]);
        $simpatizante2 = Simpatizante::factory()->create([
            'campana_id' => $campana2->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $query = $this->applyCampaignScope(Simpatizante::query(), $user);
        $results = $query->pluck('id');
        
        $this->assertTrue($results->contains($simpatizante1->id));
        $this->assertFalse($results->contains($simpatizante2->id));
    }

    public function test_apply_user_scope_filters_by_both_territory_and_campaign()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $user->campanas()->attach($campana->id);
        $user->territorios()->attach($territorio->id);
        
        $simpatizanteValido = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $otraCampana = Campana::factory()->create();
        $simpatizanteCampanaIncorrecta = Simpatizante::factory()->create([
            'campana_id' => $otraCampana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $otroTerritorio = Territorio::factory()->create();
        $simpatizanteTerritorioIncorrecto = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $otroTerritorio->id,
        ]);
        
        $query = $this->applyUserScope(Simpatizante::query(), $user);
        $results = $query->pluck('id');
        
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($simpatizanteValido->id));
    }

    public function test_superadmin_bypasses_territory_scope()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('Superadministrador');
        
        $territorio = Territorio::factory()->create();
        $campana = Campana::factory()->create();
        
        $simpatizante = Simpatizante::factory()->create([
            'territorio_id' => $territorio->id,
            'campana_id' => $campana->id,
        ]);
        
        $query = $this->applyTerritoryScope(Simpatizante::query(), $superadmin);
        $results = $query->pluck('id');
        
        $this->assertTrue($results->contains($simpatizante->id));
    }

    public function test_superadmin_bypasses_campaign_scope()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('Superadministrador');
        
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $query = $this->applyCampaignScope(Simpatizante::query(), $superadmin);
        $results = $query->pluck('id');
        
        $this->assertTrue($results->contains($simpatizante->id));
    }

    public function test_user_has_access_to_territory_returns_true_for_assigned()
    {
        $user = User::factory()->create();
        $territorio = Territorio::factory()->create();
        $user->territorios()->attach($territorio->id);
        
        $this->assertTrue($this->userHasAccessToTerritory($user, $territorio->id));
    }

    public function test_user_has_access_to_territory_returns_false_for_unassigned()
    {
        $user = User::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $this->assertFalse($this->userHasAccessToTerritory($user, $territorio->id));
    }

    public function test_user_has_access_to_campaign_returns_true_for_assigned()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $user->campanas()->attach($campana->id);
        
        $this->assertTrue($this->userHasAccessToCampaign($user, $campana->id));
    }

    public function test_user_has_access_to_campaign_returns_false_for_unassigned()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        
        $this->assertFalse($this->userHasAccessToCampaign($user, $campana->id));
    }

    public function test_get_accessible_territories_returns_assigned_territories()
    {
        $user = User::factory()->create();
        $territorio1 = Territorio::factory()->create();
        $territorio2 = Territorio::factory()->create();
        
        $user->territorios()->attach([$territorio1->id, $territorio2->id]);
        
        $territorios = $this->getAccessibleTerritories($user);
        
        $this->assertIsArray($territorios);
        $this->assertCount(2, $territorios);
        $this->assertContains($territorio1->id, $territorios);
        $this->assertContains($territorio2->id, $territorios);
    }

    public function test_get_accessible_campaigns_returns_assigned_campaigns()
    {
        $user = User::factory()->create();
        $campana1 = Campana::factory()->create();
        $campana2 = Campana::factory()->create();
        
        $user->campanas()->attach([$campana1->id, $campana2->id]);
        
        $campanas = $this->getAccessibleCampaigns($user);
        
        $this->assertIsArray($campanas);
        $this->assertCount(2, $campanas);
        $this->assertContains($campana1->id, $campanas);
        $this->assertContains($campana2->id, $campanas);
    }

    public function test_apply_territory_scope_returns_no_results_for_user_without_territories()
    {
        $user = User::factory()->create();
        $territorio = Territorio::factory()->create();
        $campana = Campana::factory()->create();
        
        $simpatizante = Simpatizante::factory()->create([
            'territorio_id' => $territorio->id,
            'campana_id' => $campana->id,
        ]);
        
        $query = $this->applyTerritoryScope(Simpatizante::query(), $user);
        $results = $query->get();
        
        $this->assertCount(0, $results);
    }

    public function test_apply_campaign_scope_returns_no_results_for_user_without_campaigns()
    {
        $user = User::factory()->create();
        $campana = Campana::factory()->create();
        $territorio = Territorio::factory()->create();
        
        $simpatizante = Simpatizante::factory()->create([
            'campana_id' => $campana->id,
            'territorio_id' => $territorio->id,
        ]);
        
        $query = $this->applyCampaignScope(Simpatizante::query(), $user);
        $results = $query->get();
        
        $this->assertCount(0, $results);
    }
}
