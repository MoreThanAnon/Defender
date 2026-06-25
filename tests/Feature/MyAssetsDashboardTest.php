<?php

namespace Tests\Feature;

use App\Models\AssetOperationsChecklist;
use App\Models\DecidimGovernanceSpace;
use App\Models\DecidimGovernanceSync;
use App\Models\EquityClass;
use App\Models\EquityPosition;
use App\Models\LoanNote;
use App\Models\Offering;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MyAssetsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_investor_can_view_funded_assets_and_governance_status(): void
    {
        DB::table('properties_status')->insertOrIgnore([
            ['id' => '1', 'status' => 'Active', 'description' => 'Test active status'],
        ]);

        $investor = User::factory()->create(['username' => 'assetinvestor']);
        $property = Property::factory()->create(['name' => 'Investor Shop Asset']);
        $equityClass = EquityClass::create([
            'property_id' => $property->id,
            'code' => 'ORD',
            'name' => 'Ordinary equity',
            'voting_rights' => ['ordinary_property_control' => true],
        ]);
        $offering = Offering::create([
            'property_id' => $property->id,
            'name' => 'Investor shop offering',
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

        EquityPosition::create([
            'offering_id' => $offering->id,
            'equity_class_id' => $equityClass->id,
            'holder_user_id' => $investor->id,
            'holder_type' => 'USER',
            'pct_ownership' => '15.0000',
            'source' => 'INVESTOR_POOL',
            'position_type' => 'LOCAL_INVESTOR',
            'voting_rights' => true,
            'can_vote' => true,
            'voting_bp' => 1500,
            'economic_bp' => 1500,
        ]);

        LoanNote::create([
            'offering_id' => $offering->id,
            'lender_user_id' => $investor->id,
            'investor_id' => $investor->id,
            'principal' => '250.00',
            'principal_cents' => 25000,
            'interest_rate_bp' => 500,
            'term_months' => 24,
            'repayment_frequency' => 'monthly',
            'status' => 'active',
            'ranking' => 'senior_unsecured',
            'voting_scope' => 'debt_protective_only',
        ]);

        DecidimGovernanceSpace::create([
            'offering_id' => $offering->id,
            'property_id' => $property->id,
            'decidim_assembly_id' => 'assembly_1',
            'decidim_assembly_slug' => 'investor-shop-asset',
            'decidim_assembly_url' => 'https://decidim.test/assemblies/investor-shop-asset',
            'status' => 'created',
        ]);

        DecidimGovernanceSync::create([
            'offering_id' => $offering->id,
            'investor_id' => $investor->id,
            'decidim_user_id' => 'decidim_user_1',
            'role' => 'local_equity_voter',
            'can_vote' => true,
            'voting_bp' => 1500,
            'voting_scope' => 'ordinary_property_control',
            'synced_at' => now(),
        ]);

        AssetOperationsChecklist::create([
            'offering_id' => $offering->id,
            'property_id' => $property->id,
            'bank_balance' => '1234.56',
            'bills_due' => [['label' => 'Rates bill', 'done' => false]],
            'maintenance_tasks' => [['label' => 'Lease review', 'done' => false]],
            'reviewed_at' => now(),
        ]);

        $this->actingAs($investor)
            ->get(route('my-assets'))
            ->assertOk()
            ->assertSee('My Assets')
            ->assertSee('Investor Shop Asset')
            ->assertSee('Open Decidim Assembly')
            ->assertSee('Eligible')
            ->assertSee('15.0000%')
            ->assertSee('EUR 250.00')
            ->assertSee('EUR 1,234.56');
    }
}
