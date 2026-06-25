<?php

namespace Tests\Feature;

use App\Models\DecidimGovernanceSpace;
use App\Models\DecidimGovernanceSync;
use App\Models\Offering;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDecidimDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_decidim_integration_status(): void
    {
        DB::table('properties_status')->insertOrIgnore([
            ['id' => '1', 'status' => 'Active', 'description' => 'Test active status'],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $property = Property::factory()->create();
        $offering = Offering::create([
            'property_id' => $property->id,
            'name' => 'Dashboard test offering',
            'status' => 'SETTLED',
            'target_raise' => '1000.00',
            'target_raise_cents' => 100000,
            'hard_cap_cents' => 100000,
            'org_reserve_pct' => '10.00',
            'min_local_capital_pct' => 0,
            'min_local_investor_count' => 1,
            'require_local_threshold_for_settlement' => true,
            'campaign_end' => now()->subDay(),
        ]);

        DecidimGovernanceSpace::create([
            'offering_id' => $offering->id,
            'property_id' => $property->id,
            'decidim_assembly_id' => 'assembly_1',
            'decidim_assembly_slug' => 'dashboard-test-assembly',
            'decidim_assembly_url' => 'https://decidim.test/assemblies/dashboard-test-assembly',
            'decidim_debates_component_id' => 'debates_1',
            'decidim_proposals_component_id' => 'proposals_1',
            'decidim_meetings_component_id' => 'meetings_1',
            'status' => 'created',
        ]);

        DecidimGovernanceSync::create([
            'offering_id' => $offering->id,
            'investor_id' => $admin->id,
            'decidim_user_id' => 'decidim_user_1',
            'role' => 'local_equity_voter',
            'can_vote' => true,
            'voting_bp' => 1500,
            'voting_scope' => 'ordinary_property_control',
            'synced_at' => now(),
            'payload' => ['test' => true],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.decidim.index'))
            ->assertOk()
            ->assertSee('Decidim Integration Status')
            ->assertSee('dashboard-test-assembly')
            ->assertSee('Legal review required')
            ->assertSee('Synced investor roles')
            ->assertSee('Retry sync');
    }
}
