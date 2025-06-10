<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;

class PropertyOwnershipBreakdownTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_ownership_breakdown_correctly()
    {
        // Create a property
        $property = Property::factory()->create();

        // Create users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Mock investments relationship
        $mockInvestments = collect([
            (object)['user_id' => $user1->id, 'amount' => 100, 'type' => 'equity'],
            (object)['user_id' => $user2->id, 'amount' => 200, 'type' => 'equity'],
        ]);

        $property = Mockery::mock(args: Property::class)->makePartial();
        $property->shouldReceive('investments')->andReturn(
            new class($mockInvestments) {
                private $investments;
                public function __construct($investments) { $this->investments = $investments; }
                public function where($column, $value) {
                    return new class($this->investments->where($column, $value)) {
                        private $filtered;
                        public function __construct($filtered) { $this->filtered = $filtered; }
                        public function get() { return $this->filtered; }
                        public function groupBy($column) { return $this->filtered->groupBy($column); }
                    };
                }
            }
        );

        // Calculate expected values
        $total = 100 + 200;
        $expected = [
            $user1->id => round((100 / $total) * 90, 2),
            $user2->id => round((200 / $total) * 90, 2),
        ];

        $result = $property->calculateOwnershipBreakdown();
        var_Dump($result);die;

        $this->assertEquals($expected, $result);
    }
}