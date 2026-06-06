<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertyFundingStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('properties_status')->insertOrIgnore([
            ['id' => '1', 'status' => 'Active', 'description' => 'Test active status'],
            ['id' => '2', 'status' => 'Inactive', 'description' => 'Test inactive status'],
        ]);
    }

    public function test_property_funding_status_returns_current_progress_totals(): void
    {
        $property = Property::factory()->create(['price' => 250000]);
        $user = User::factory()->create();

        Bid::create([
            'property_id' => $property->id,
            'user_id' => $user->id,
            'amount' => '12500.00',
            'status' => 'PLEDGED',
        ]);

        $this->getJson(route('properties.fundingStatus', $property->id))
            ->assertOk()
            ->assertJsonPath('collectiveFund', 12500)
            ->assertJsonPath('fundGoal', 250000)
            ->assertJsonPath('percentage', 5);
    }
}
